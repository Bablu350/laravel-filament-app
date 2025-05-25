<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class ChangePassword extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public ?string $current_password = null;
    public ?string $new_password = null;
    public ?string $new_password_confirmation = null;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static string $view = 'filament.pages.change-password';
    protected static ?string $title = 'Change Password';

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('current_password')
                ->label('Current Password')
                ->password()
                ->required(),
            Forms\Components\TextInput::make('new_password')
                ->label('New Password')
                ->password()
                ->required()
                ->confirmed(),
            Forms\Components\TextInput::make('new_password_confirmation')
                ->label('Confirm New Password')
                ->password()
                ->required(),
        ];
    }

    public function submit(): void
    {
        $this->validate();

        if (! Hash::check($this->current_password, auth()->user()->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        auth()->user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        Notification::make()
            ->title('Password updated successfully.')
            ->success()
            ->send();
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
