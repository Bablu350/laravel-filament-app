<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Rules\ValidIfscCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: fn($record) => $record),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn($livewire) => $livewire instanceof Pages\CreateUser)
                            ->minLength(8)
                            ->dehydrated(fn($state) => filled($state)),
                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->required()
                            ->maxDate(now()),
                    ])->columns(2),
                Forms\Components\Section::make('Identity Information')
                    ->schema([
                        Forms\Components\TextInput::make('aadhaar_number')
                            ->label('Aadhaar Number')
                            ->required()
                            ->maxLength(12)
                            ->minLength(12)
                            ->numeric()
                            ->unique(
                                table: User::class,
                                column: 'aadhaar_number',
                                ignorable: fn($record) => $record,
                            ),
                        Forms\Components\FileUpload::make('aadhaar_card')
                            ->label('Aadhaar Card (Photo/PDF)')
                            ->required()
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('aadhaar_cards')
                            ->downloadable()
                            ->previewable(true)
                            ->openable()
                            ->visibility('private')
                            ->maxSize(5120),
                        Forms\Components\TextInput::make('pan_number')
                            ->label('PAN Number')
                            ->maxLength(10)
                            ->minLength(10)
                            ->unique(
                                table: User::class,
                                column: 'pan_number',
                                ignorable: fn($record) => $record,
                            ),
                        Forms\Components\FileUpload::make('pan_card')
                            ->label('PAN Card (Photo/PDF)')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('pan_cards')
                            ->downloadable()
                            ->previewable(true)
                            ->openable()
                            ->visibility('private')
                            ->maxSize(5120),
                        Forms\Components\TextInput::make('voter_id_number')
                            ->label('Voter ID Number')
                            ->maxLength(20)
                            ->unique(
                                table: User::class,
                                column: 'voter_id_number',
                                ignorable: fn($record) => $record,
                            ),
                        Forms\Components\FileUpload::make('voter_id_card')
                            ->label('Voter ID Card (Photo/PDF)')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('voter_id_cards')
                            ->downloadable()
                            ->previewable(true)
                            ->openable()
                            ->visibility('private')
                            ->maxSize(5120),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Address Information')
                    ->schema([
                        Forms\Components\TextInput::make('pincode')
                            ->required()
                            ->maxLength(6)
                            ->minLength(6),
                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->columns(2),
                    Forms\Components\Section::make('Bank Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Bank Account Number')
                            ->required()
                            ->numeric()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('ifsc_code')
                            ->label('IFSC Code')
                            ->required()
                            ->maxLength(11)
                            ->minLength(11)
                            ->rule(new ValidIfscCode())
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    $set('bank_details_temp', null);
                                    $set('bank_details', null); // Reset bank_details
                                    return;
                                }
                                $cacheKey = 'ifsc_' . $state;
                                $bankDetails = Cache::get($cacheKey);
                                if (!$bankDetails) {
                                    try {
                                        $response = Http::timeout(5)->get('https://ifsc.razorpay.com/' . $state);
                                        if ($response->successful()) {
                                            $data = $response->json();
                                            $bankDetails = [
                                                'bank' => $data['BANK'],
                                                'branch' => $data['BRANCH'],
                                                'city' => $data['CITY'],
                                            ];
                                            Cache::put($cacheKey, $bankDetails, now()->addHours(24));
                                        } else {
                                            $bankDetails = ['error' => 'Invalid IFSC code'];
                                            throw ValidationException::withMessages([
                                                'ifsc_code' => 'The provided IFSC code is invalid.',
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        $bankDetails = ['error' => 'Invalid IFSC code'];
                                        throw ValidationException::withMessages([
                                            'ifsc_code' => 'The provided IFSC code is invalid.',
                                        ]);
                                    }
                                }
                
                                $set('bank_details_temp', $bankDetails);
                                $set('bank_details', !isset($bankDetails['error']) ? $bankDetails : null); // Set bank_details
                            }),
                        Forms\Components\Hidden::make('bank_details'), // Hidden field to store bank_details
                        Forms\Components\Placeholder::make('bank_details_display')
                            ->label('Bank Details')
                            ->content(function ($get, $record) {
                                $tempDetails = $get('bank_details_temp');
                                $storedDetails = $record?->bank_details;
                                if ($get('ifsc_code') && !$tempDetails && !$storedDetails) {
                                    return 'Fetching bank details...';
                                }
                                if ($tempDetails && !isset($tempDetails['error'])) {
                                    return "{$tempDetails['bank']} - {$tempDetails['branch']} ({$tempDetails['city']})";
                                }
                
                                if ($tempDetails && isset($tempDetails['error'])) {
                                    return $tempDetails['error'];
                                }
                
                                if ($storedDetails) {
                                    return "{$storedDetails['bank']} - {$storedDetails['branch']} ({$storedDetails['city']})";
                                }
                
                                return 'Enter a valid IFSC code to see bank details';
                            })
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('gender')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')->date()->sortable(),
                Tables\Columns\TextColumn::make('pincode')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->label('Role')->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => is_null($record->deleted_at)),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ])
            ->modifyQueryUsing(fn($query) => $query->withTrashed())
            ->recordUrl(fn($record) => is_null($record->deleted_at) ? route('filament.admin.resources.users.edit', $record) : null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['superadmin', 'admin']);
    }
}
