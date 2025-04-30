<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers\EmiRelationManager; // [NEW] Added for Relation Manager
use App\Models\Loan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon; // [NEW] Added for date manipulation
use Illuminate\Support\Facades\Auth;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Loan Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User Full Name')
                            ->options(function () {
                                return User::query()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn($livewire) => $livewire instanceof Pages\EditLoan)
                            ->dehydrated(fn($livewire) => $livewire instanceof Pages\CreateLoan)
                            ->reactive(),
                        Forms\Components\TextInput::make('loan_amount')
                            ->label('Loan Amount')
                            ->required()
                            ->numeric()
                            ->disabled(fn($livewire) => $livewire instanceof Pages\EditLoan)
                            ->minValue(1000)
                            ->maxValue(10000000)
                            ->prefix('₹')
                            ->extraInputAttributes([
                                'type' => 'text',
                                'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')",
                            ])
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $loanAge = $get('loan_age');
                                $interestRate = $get('interest_rate');
                                if ($state && $loanAge && $interestRate) {
                                    // Calculate EMI
                                    $monthlyRate = $interestRate / 1200; // Annual % to monthly decimal
                                    $n = $loanAge;
                                    if ($monthlyRate == 0) {
                                        $emi = $state / $n;
                                    } else {
                                        $emi = $state * $monthlyRate * pow(1 + $monthlyRate, $n) / (pow(1 + $monthlyRate, $n) - 1);
                                    }
                                    // $emi = $state * $monthlyRate * pow(1 + $monthlyRate, $n) / (pow(1 + $monthlyRate, $n) - 1);
                                    $set('emi_amount', round($emi, 2));
                                    $set('calculation_status', ['status' => 'EMI calculated']);
                                } else {
                                    $set('calculation_status', ['error' => 'Enter loan amount, age, and interest rate to calculate EMI']);
                                }
                            }),
                        Forms\Components\DatePicker::make('loan_start_date')
                            ->label('Loan Start Date')
                            ->required()
                            ->disabled(fn($livewire) => $livewire instanceof Pages\EditLoan)
                            ->reactive(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('First EMI Due Date')
                            ->required()
                            ->minDate(fn($get) => $get('loan_start_date'))
                            ->reactive(),
                        Forms\Components\Select::make('emi_type')
                            ->label('EMI Type')
                            ->options([
                                'weekly' => 'Weekly',
                                'bi-weekly' => 'Bi-Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Set loan age label based on EMI type
                                $set('loan_age_label', match ($state) {
                                    'weekly' => 'Loan Age (Weeks)',
                                    'bi-weekly' => 'Loan Age (Bi-Weeks)',
                                    'monthly' => 'Loan Age (Months)',
                                    default => 'Loan Age (Months)',
                                });
                            })
                            ->default('monthly') // Default to monthly to set initial label
                            ->afterStateHydrated(function ($component, $state, callable $set) {
                                // Set label when form is hydrated (e.g., on edit)
                                $set('loan_age_label', match ($state) {
                                    'weekly' => 'Loan Age (Weeks)',
                                    'bi-weekly' => 'Loan Age (Bi-Weeks)',
                                    'monthly' => 'Loan Age (Months)',
                                    default => 'Loan Age (Months)',
                                });
                            }),
                        Forms\Components\TextInput::make('loan_age')
                            ->label(function ($get) {
                                return $get('loan_age_label') ?? 'Loan Age (Months)';
                            })
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(360)
                            ->extraInputAttributes([
                                'type' => 'text',
                                'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')",
                            ])
                            ->reactive()
                            ->lazy()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $loanAmount = $get('loan_amount');
                                $interestRate = $get('interest_rate');
                                if ($loanAmount && $state && $interestRate) {
                                    // Calculate EMI
                                    $monthlyRate = $interestRate / 1200;
                                    $n = $state;
                                    if ($monthlyRate == 0) {
                                        $emi = $loanAmount / $n;
                                    } else {
                                        $emi = $loanAmount * $monthlyRate * pow(1 + $monthlyRate, $n) / (pow(1 + $monthlyRate, $n) - 1);
                                    }
                                    $set('emi_amount', round($emi, 2));
                                    $set('calculation_status', ['status' => 'EMI calculated']);
                                } else {
                                    $set('calculation_status', ['error' => 'Enter loan amount, age, and interest rate to calculate EMI']);
                                }
                            }),
                        Forms\Components\TextInput::make('interest_rate')
                            ->label('Interest Rate (%)')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(50)
                            ->extraInputAttributes([
                                'type' => 'text',
                                'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')",
                            ])
                            ->reactive()
                            ->lazy()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $loanAmount = $get('loan_amount');
                                $loanAge = $get('loan_age');
                                if ($loanAmount && $loanAge && $state != null) {
                                    $monthlyRate = $state / 1200;
                                    $n = $loanAge;
                                    if ($monthlyRate == 0) {
                                        $emi = $loanAmount / $n;
                                    } else {
                                        $emi = $loanAmount * $monthlyRate * pow(1 + $monthlyRate, $n) / (pow(1 + $monthlyRate, $n) - 1);
                                    }
                                    $set('emi_amount', round($emi, 2));
                                    $set('calculation_status', ['status' => 'EMI calculated']);
                                } else {
                                    $set('calculation_status', ['error' => 'Enter loan amount, age, and interest rate to calculate EMI']);
                                }
                            }),
                        Forms\Components\TextInput::make('emi_amount')
                            ->label('EMI Amount')
                            ->required()
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(1000000)
                            ->prefix('₹')
                            ->extraInputAttributes([
                                'type' => 'text',
                                'oninput' => "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')",
                            ])
                            ->reactive()
                            ->lazy()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $loanAmount = $get('loan_amount');
                                $loanAge = $get('loan_age');
                                if ($loanAmount && $loanAge && $state) {
                                    $n = $loanAge;
                                    $p = $loanAmount;
                                    $emi = $state;
                                    $monthlyGuess = 0.01; // initial guess

                                    for ($i = 0; $i < 100; $i++) {
                                        $pow = pow(1 + $monthlyGuess, $n);
                                        $calculatedEmi = $p * $monthlyGuess * $pow / ($pow - 1);
                                        $f = $calculatedEmi - $emi;

                                        // derivative approximation
                                        $delta = 0.000001;
                                        $powDelta = pow(1 + $monthlyGuess + $delta, $n);
                                        $calculatedEmiDelta = $p * ($monthlyGuess + $delta) * $powDelta / ($powDelta - 1);
                                        $fPrime = ($calculatedEmiDelta - $calculatedEmi) / $delta;

                                        $newGuess = $monthlyGuess - $f / $fPrime;
                                        if (abs($newGuess - $monthlyGuess) < 0.0000001) {
                                            break;
                                        }
                                        $monthlyGuess = $newGuess;
                                    }

                                    $annualRate = $monthlyGuess * 1200;
                                    if ($annualRate >= 0 && $annualRate <= 50) {
                                        $set('interest_rate', round($annualRate, 2));
                                        $set('calculation_status', ['status' => 'Interest rate calculated']);
                                    } else {
                                        $set('calculation_status', ['error' => 'Calculated interest rate is out of valid range (0.1% to 50%)']);
                                    }
                                } else {
                                    $set('calculation_status', ['error' => 'Enter loan amount, age, and EMI to calculate interest rate']);
                                }
                            }),
                        Forms\Components\TextInput::make('total_amount_paid')
                            ->label('Total Amount Paid')
                            ->numeric()
                            ->prefix('₹')
                            ->disabled()
                            ->reactive()
                            ->default(0),
                        Forms\Components\Placeholder::make('calculation_status')
                            ->label('Calculation Status')
                            ->content(function ($get) {
                                $validation = $get('calculation_status');
                                if ($validation && isset($validation['status'])) {
                                    return $validation['status'];
                                }
                                if ($validation && isset($validation['error'])) {
                                    return $validation['error'];
                                }
                                return 'Enter loan details to calculate EMI or interest rate';
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User Full Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('loan_amount')
                    ->label('Loan Amount')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('First EMI Due Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('interest_rate')
                    ->label('Interest Rate (%)')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_age')
                    ->label('Loan Age')
                    ->sortable(),
                Tables\Columns\TextColumn::make('emi_type')
                    ->label('EMI Type')
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('-', ' ', $state)))
                    ->sortable(),
                Tables\Columns\TextColumn::make('emi_amount')
                    ->label('EMI Amount')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount_paid')
                    ->label('Total Amount Paid')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->withTrashed());
    }

    // [NEW] Added Relation Manager
    public static function getRelations(): array
    {
        return [
            EmiRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->hasAnyRole(['superadmin', 'admin']);
    }
}
