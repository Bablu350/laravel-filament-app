<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class EmiRelationManager extends RelationManager
{
    protected static string $relationship = 'emis';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('due_date')
                    ->label('Due Date')
                    ->required()
                    ->disabled(fn($livewire) => $livewire->ownerRecord->exists),
                Forms\Components\TextInput::make('emi_amount')
                    ->label('EMI Amount')
                    ->required()
                    ->numeric()
                    ->minValue(100)
                    ->maxValue(1000000)
                    ->prefix('₹')
                    ->disabled(fn($livewire) => $livewire->ownerRecord->exists),
                Forms\Components\TextInput::make('emi_paid_amount')
                    ->label('EMI Paid Amount')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1000000)
                    ->prefix('₹')
                    ->default(0),
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Payment Date')
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('due_date')
            ->columns([
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('emi_amount')
                    ->label('EMI Amount')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('emi_paid_amount')
                    ->label('EMI Paid Amount')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->disabled(fn($livewire) => $livewire->ownerRecord->exists),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ])
            ->modifyQueryUsing(fn($query) => $query->orderBy('due_date'));
    }

    #[On('refreshRelationManager')]
    public function refreshTable($data): void
    {
        // Log::info('Received refresh-emi-relation-manager event', ['data' => $data]);
        // if ($data['relationManager'] === static::class) {
        //     $this->table->refreshRecords();
        // }
    }
}
