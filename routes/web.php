<?php

use Illuminate\Support\Facades\Route;
// use Filament\Facades\Filament;

// Filament::registerRoutes();
Route::get('/', function () {
    return view('welcome');
});
