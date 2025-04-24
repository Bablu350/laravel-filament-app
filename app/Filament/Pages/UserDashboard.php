<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UserDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.user-dashboard';

    // ✅ Rename the page to just "Dashboard"
    protected static ?string $title = 'Dashboard';

    // // ✅ Remove from sidebar
    // public static function shouldRegisterNavigation(): bool
    // {
    //     return false;
    // }

    // ✅ Only accessible to users with role "user"
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('user');
    }
}