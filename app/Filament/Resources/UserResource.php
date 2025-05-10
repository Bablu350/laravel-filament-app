<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Rules\ValidIfscCode;
use App\Rules\ValidPanNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        $isAdmin = auth()->user()->hasRole('admin');
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
                            ->default(function () use ($isAdmin) {
                                if ($isAdmin) {
                                    return \Spatie\Permission\Models\Role::where('name', 'user')->first()?->id;
                                }
                                return null;
                            })
                            ->disabled($isAdmin)
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
                        Forms\Components\Toggle::make('p_info_verified')
                            ->label('Personal Info Verified')
                            ->disabled($isAdmin)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('user_verified', false);
                                }
                            })
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                        Forms\Components\Placeholder::make('p_info_verified_by')
                            ->label('Verified By')
                            ->content(fn($record) => $record?->p_info_verifier?->name ?? 'N/A')
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                    ])->columns(3),
                Forms\Components\Section::make('Identity Information')
                    ->schema([
                        Forms\Components\TextInput::make('aadhaar_number')
                            ->label('Aadhaar Number')
                            ->required()
                            ->maxLength(12)
                            ->minLength(12)
                            ->numeric()
                            ->extraInputAttributes(['type' => 'text', 'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"])
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
                            ->rule(new ValidPanNumber())
                            ->reactive()
                            ->lazy()
                            ->debounce(500)
                            ->extraInputAttributes([
                                'style' => 'text-transform: uppercase;',
                                'oninput' => 'this.value = this.value.toUpperCase();',
                            ])
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    $set('pan_validation', null);
                                    return;
                                }
                                $set('pan_validation', ['status' => 'Format valid']);
                            })
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
                        Forms\Components\Toggle::make('doc_verified')
                            ->label('Documents Verified')
                            ->disabled($isAdmin)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('user_verified', false);
                                }
                            })
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                        Forms\Components\Placeholder::make('doc_verified_by')
                            ->label('Verified By')
                            ->content(fn($record) => $record?->doc_verifier?->name ?? 'N/A')
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
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
                        Forms\Components\Toggle::make('address_verified')
                            ->label('Address Verified')
                            ->disabled($isAdmin)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('user_verified', false);
                                }
                            })
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                        Forms\Components\Placeholder::make('address_verified_by')
                            ->label('Verified By')
                            ->content(fn($record) => $record?->address_verifier?->name ?? 'N/A')
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Bank Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Bank Account Number')
                            ->required()
                            ->extraInputAttributes(['type' => 'text', 'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"])
                            ->regex('/^[0-9]{9,20}$/')
                            ->numeric()
                            ->reactive()
                            ->minLength(9)
                            ->maxLength(20),
                        Forms\Components\TextInput::make('ifsc_code')
                            ->label('IFSC Code')
                            ->required()
                            ->maxLength(11)
                            ->minLength(11)
                            ->rule(new ValidIfscCode())
                            ->reactive()
                            ->debounce(500)
                            ->lazy()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    $set('bank_details_temp', null);
                                    $set('bank_details', null);
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
                                $set('bank_details', !isset($bankDetails['error']) ? $bankDetails : null);
                            }),
                        Forms\Components\Hidden::make('bank_details'),
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
                            }),
                        Forms\Components\Toggle::make('bank_verified')
                            ->label('Bank Verified')
                            ->disabled($isAdmin)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('user_verified', false);
                                }
                            })
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                        Forms\Components\Placeholder::make('bank_verified_by')
                            ->label('Verified By')
                            ->content(fn($record) => $record?->bank_verifier?->name ?? 'N/A')
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Verification Status')
                    ->schema([
                        Forms\Components\Toggle::make('user_verified')
                            ->label('User Fully Verified')
                            ->disabled(function ($get, $record) use ($isAdmin) {
                                if ($isAdmin || !$record) {
                                    return true;
                                }
                                return !($get('p_info_verified') && $get('doc_verified') && $get('address_verified') && $get('bank_verified'));
                            })
                            ->reactive()
                            ->dehydrated()
                            ->helperText('Enabled only when all verification fields are checked.')
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                        Forms\Components\Placeholder::make('user_verified_by')
                            ->label('User Verified By')
                            ->content(fn($record) => $record?->user_verifier?->name ?? 'N/A')
                            ->visible($isAdmin ? fn($record) => $record !== null : true),
                    ])
                    ->columns(2)
                    ->visible($isAdmin ? fn($record) => $record !== null : true),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('superadmin');
        $isAdmin = $user->hasRole('admin');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('gender')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')->date()->sortable(),
                Tables\Columns\TextColumn::make('pincode')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->label('Role')->sortable(),
                Tables\Columns\IconColumn::make('p_info_verified')
                    ->label('Personal Info Verified')
                    ->boolean()
                    ->sortable()
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('p_info_verifier.name')
                    ->label('Personal Info Verified By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A')
                    ->visible($isSuperAdmin),
                Tables\Columns\IconColumn::make('doc_verified')
                    ->label('Documents Verified')
                    ->boolean()
                    ->sortable()
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('doc_verifier.name')
                    ->label('Documents Verified By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A')
                    ->visible($isSuperAdmin),
                Tables\Columns\IconColumn::make('address_verified')
                    ->label('Address Verified')
                    ->boolean()
                    ->sortable()
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('address_verifier.name')
                    ->label('Address Verified By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A')
                    ->visible($isSuperAdmin),
                Tables\Columns\IconColumn::make('bank_verified')
                    ->label('Bank Verified')
                    ->boolean()
                    ->sortable()
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('bank_verifier.name')
                    ->label('Bank Verified By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A')
                    ->visible($isSuperAdmin),
                Tables\Columns\IconColumn::make('user_verified')
                    ->label('User Verified')
                    ->boolean()
                    ->sortable()
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('user_verifier.name')
                    ->label('User Verified By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A')
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A')
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Updated By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A')
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('deleter.name')
                    ->label('Deleted By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A')
                    ->visible($isSuperAdmin),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($isSuperAdmin),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->visible($isSuperAdmin),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => is_null($record->deleted_at) && ($isSuperAdmin || ($isAdmin && $record->hasRole('user')))),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(User $record) => $record->id === auth()->user()->id || ($isAdmin && !$record->hasRole('user')))
                    ->visible($isSuperAdmin || $isAdmin),
                Tables\Actions\ForceDeleteAction::make()
                    ->hidden(fn(User $record) => $record->id === auth()->user()->id)
                    ->visible(fn($record) => $isSuperAdmin && !is_null($record->deleted_at)),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn($record) => $isSuperAdmin && !is_null($record->deleted_at)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records, $action) {
                        if ($records->contains(fn(User $record) => $record->id === auth()->user()->id)) {
                            Notification::make()
                                ->title('Cannot delete your own account')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->visible($isSuperAdmin),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->before(function ($records, $action) {
                        if ($records->contains(fn(User $record) => $record->id === auth()->user()->id)) {
                            Notification::make()
                                ->title('Cannot delete your own account')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->visible($isSuperAdmin),
                Tables\Actions\RestoreBulkAction::make()
                    ->visible($isSuperAdmin),
            ])
            ->modifyQueryUsing(function ($query) use ($isSuperAdmin, $isAdmin) {
                if ($isSuperAdmin) {
                    return $query->withTrashed()->with(['creator', 'updater', 'deleter', 'p_info_verifier', 'doc_verifier', 'address_verifier', 'bank_verifier', 'user_verifier']);
                } elseif ($isAdmin) {
                    return $query->whereHas('roles', fn($q) => $q->where('name', 'user'));
                }
                return $query;
            })
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
