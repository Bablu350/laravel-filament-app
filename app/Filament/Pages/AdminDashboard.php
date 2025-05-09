<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.admin-dashboard';

    // Rename the page to "Dashboard"
    protected static ?string $title = 'Dashboard';

    // Restrict access to superadmin and admin roles
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['superadmin', 'admin']);
    }
}
