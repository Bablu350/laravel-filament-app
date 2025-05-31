<?php

namespace App\Filament\Pages;

use App\Rules\ValidIfscCode;
use App\Rules\ValidPanNumber;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MyProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static string $view = 'filament.pages.my-profile';

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $title = 'My Profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(auth()->user()->p_info_verified ? 'Personal Information - Verified' : 'Personal Information')
                    ->icon('heroicon-o-check-circle')
                    ->iconColor(fn() => auth()->user()->p_info_verified ? 'success' : null)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(function ($get) {
                                return $get('p_info_verified') === true;
                            }),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: fn($record) => auth()->user())
                            ->disabled(function ($get) {
                                return $get('p_info_verified') === true;
                            }),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->disabled(function ($get) {
                                return $get('p_info_verified') === true;
                            }),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->required()
                            ->maxDate(now())
                            ->disabled(function ($get) {
                                return $get('p_info_verified') === true;
                            }),
                    ])
                    ->columns(4),
                Forms\Components\Section::make(auth()->user()->doc_verified ? 'Identity Information - Verified' : 'Identity Information')
                    ->icon('heroicon-o-check-circle')
                    ->iconColor(fn() => auth()->user()->doc_verified ? 'success' : null)
                    ->schema([
                        Forms\Components\TextInput::make('aadhaar_number')
                            ->label('Aadhaar Number')
                            ->required()
                            ->maxLength(12)
                            ->minLength(12)
                            ->numeric()
                            ->extraInputAttributes(['type' => 'text', 'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"])
                            ->unique(
                                table: \App\Models\User::class,
                                column: 'aadhaar_number',
                                ignorable: fn($record) => auth()->user(),
                            )
                            ->disabled(function ($get) {
                                return $get('doc_verified') === true;
                            }),
                        Forms\Components\FileUpload::make('aadhaar_card')
                            ->label('Aadhaar Card (Photo/PDF)')
                            ->required()
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('aadhaar_cards')
                            ->downloadable()
                            ->previewable(true)
                            ->openable()
                            ->visibility('private')
                            ->maxSize(5120)
                            ->disabled(function ($get) {
                                return $get('doc_verified') === true;
                            }),
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
                                table: \App\Models\User::class,
                                column: 'pan_number',
                                ignorable: fn($record) => auth()->user(),
                            )
                            ->disabled(function ($get) {
                                return $get('doc_verified') === true;
                            }),
                        Forms\Components\FileUpload::make('pan_card')
                            ->label('PAN Card (Photo/PDF)')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('pan_cards')
                            ->downloadable()
                            ->previewable(true)
                            ->openable()
                            ->visibility('private')
                            ->maxSize(5120)
                            ->disabled(function ($get) {
                                return $get('doc_verified') === true;
                            }),
                        Forms\Components\TextInput::make('voter_id_number')
                            ->label('Voter ID Number')
                            ->maxLength(20)
                            ->unique(
                                table: \App\Models\User::class,
                                column: 'voter_id_number',
                                ignorable: fn($record) => auth()->user(),
                            )
                            ->disabled(function ($get) {
                                return $get('doc_verified') === true;
                            }),
                        Forms\Components\FileUpload::make('voter_id_card')
                            ->label('Voter ID Card (Photo/PDF)')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('voter_id_cards')
                            ->downloadable()
                            ->previewable(true)
                            ->openable()
                            ->visibility('private')
                            ->maxSize(5120)
                            ->disabled(function ($get) {
                                return $get('doc_verified') === true;
                            }),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(auth()->user()->address_verified ? 'Address Information - Verified' : 'Address Information')
                    ->icon('heroicon-o-check-circle')
                    ->iconColor(fn() => auth()->user()->address_verified ? 'success' : null)
                    ->schema([
                        Forms\Components\TextInput::make('pincode')
                            ->required()
                            ->maxLength(6)
                            ->minLength(6)
                            ->disabled(function ($get) {
                                return $get('address_verified') === true;
                            }),
                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->maxLength(500)
                            ->disabled(function ($get) {
                                return $get('address_verified') === true;
                            }),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(auth()->user()->bank_verified ? 'Bank Information - Verified' : 'Bank Information')
                    ->icon('heroicon-o-check-circle')
                    ->iconColor(fn() => auth()->user()->bank_verified ? 'success' : null)
                    ->schema([
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Bank Account Number')
                            ->required()
                            ->extraInputAttributes(['type' => 'text', 'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"])
                            ->regex('/^[0-9]{9,20}$/')
                            ->numeric()
                            ->reactive()
                            ->minLength(9)
                            ->maxLength(20)
                            ->disabled(function ($get) {
                                return $get('bank_verified') === true;
                            }),
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
                            })
                            ->disabled(function ($get) {
                                return $get('bank_verified') === true;
                            }),
                        Forms\Components\Hidden::make('bank_details'),
                        Forms\Components\Placeholder::make('bank_details_display')
                            ->label('Bank Details')
                            ->content(function ($get, $record) {
                                $tempDetails = $get('bank_details_temp');
                                $storedDetails = auth()->user()->bank_details;
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
                    ])
                    ->columns(3),
            ])
            ->statePath('data')
            ->model(auth()->user());
    }

    protected function getFormModel(): \App\Models\User
    {
        return auth()->user();
    }

    public function submit(): void
    {
        $user = auth()->user();
        $user->update($this->form->getState());
        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    public function getActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('submit')
                ->disabled(fn() => auth()->user()->user_verified)
                ->color('primary'),
        ];
    }
}
