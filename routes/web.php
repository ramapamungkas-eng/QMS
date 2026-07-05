<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

// Users will be redirected to this route if not logged in
Route::livewire('/login', 'pages::login')->name('login');

// Define the logout
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
});

Route::middleware('auth')->group(function () {

    Route::livewire('/', 'pages::index');

    Route::prefix('inspections')->group(function () {

        Route::livewire('/stamping', 'pages::inspections.stamping.index')
            ->middleware('process:Stamping')
            ->name('inspections.stamping.index');

        Route::livewire('/stamping/create', 'pages::inspections.stamping.create')
            ->middleware('process:Stamping')
            ->name('inspections.stamping.create');

        Route::livewire('/station-spot', 'pages::inspections.station-spot.index')
            ->middleware('process:Welding')
            ->name('inspections.station-spot.index');

        Route::livewire('/station-spot/create', 'pages::inspections.station-spot.create')
            ->middleware('process:Welding')
            ->name('inspections.station-spot.create');

        Route::livewire('/portable-spot', 'pages::inspections.portable-spot.index')
            ->middleware('process:Welding')
            ->name('inspections.portable-spot.index');

        Route::livewire('/portable-spot/create', 'pages::inspections.portable-spot.create')
            ->middleware('process:Welding')
            ->name('inspections.portable-spot.create');

        Route::livewire('/robot-spot', 'pages::inspections.robot-spot.index')
            ->middleware('process:Welding')
            ->name('inspections.robot-spot.index');

        Route::livewire('/robot-spot/create', 'pages::inspections.robot-spot.create')
            ->middleware('process:Welding')
            ->name('inspections.robot-spot.create');

        // Route::livewire('/welding', 'pages::inspections.welding.index')
        //    ->middleware('process:Welding')
        //    ->name('inspections.welding.index');
    });

    Route::middleware(EnsureUserIsAdmin::class)->group(function () {

        Route::prefix('users')->group(function () {
            Route::livewire('/', 'pages::users.index')->name('users.index');
            Route::livewire('/create', 'pages::users.create')->name('users.create');
            Route::livewire('/{user}/edit', 'pages::users.edit')->name('users.edit');
        });

        Route::prefix('hardware')->group(function () {
            Route::livewire('/', 'pages::hardware.index')->name('hardware.index');
            Route::livewire('/create', 'pages::hardware.create')->name('hardware.create');
            Route::livewire('/{hardwareType}/edit', 'pages::hardware.edit')->name('hardware.edit');
        });

        Route::prefix('parts')->group(function () {
            Route::livewire('/', 'pages::parts.index')->name('parts.index');
            Route::livewire('/create', 'pages::parts.create')->name('parts.create');
            Route::livewire('/{part}/edit', 'pages::parts.edit')->name('parts.edit');
        });

        Route::prefix('work-stations')->group(function () {
            Route::livewire('/', 'pages::work-stations.index')->name('work-stations.index');
            Route::livewire('/create', 'pages::work-stations.create')->name('work-stations.create');
            Route::livewire('/{work}/edit', 'pages::work-stations.edit')->name('work-stations.edit');
        });

    });

});
