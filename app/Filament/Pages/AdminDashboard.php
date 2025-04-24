<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\User;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;

class AdminDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.admin-dashboard';

    // Rename the page to "Dashboard"
    protected static ?string $title = 'Dashboard';

    // Restrict access to superadmin and admin roles
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['superadmin', 'admin']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query())
            ->columns([
                TextColumn::make('name')->label('Name')->sortable()->searchable(),
                TextColumn::make('email')->label('Email')->sortable()->searchable(),
                TextColumn::make('roles.name')->label('Roles')->sortable()
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->multiple(),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->url(fn(User $record): string => route('filament.admin.resources.users.edit', $record))
                    ->icon('heroicon-o-pencil'),
                Action::make('delete')
                    ->label('Delete')
                    ->action(fn(User $record) => $record->delete())
                    ->requiresConfirmation()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
