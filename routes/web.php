<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

    Route::livewire('/profile', 'pages::profile.index')->name('profile.index');

    Route::prefix('reports')->group(function () {
        Route::livewire('/', 'pages::reports.index')->name('reports.index');
        Route::get('/download/{filename}', function (string $filename) {
            $path = 'reports/'.$filename;

            if (! Storage::disk('public')->exists($path)) {
                abort(404, 'Report not found.');
            }

            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];

            return Storage::disk('public')->download($path, $filename, $headers);
        })->name('reports.download');
    });

    Route::prefix('inspections')->group(function () {
        app('inspections.routes')->register();
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
            Route::livewire('/tutorial', 'pages::parts.tutorial')->name('parts.tutorial');
        });

        Route::prefix('work-stations')->group(function () {
            Route::livewire('/', 'pages::work-stations.index')->name('work-stations.index');
            Route::livewire('/create', 'pages::work-stations.create')->name('work-stations.create');
            Route::livewire('/{workStation}/edit', 'pages::work-stations.edit')->name('work-stations.edit');
        });

        Route::prefix('checklists')->group(function () {
            Route::livewire('/', 'pages::checklists.index')->name('checklists.index');
            Route::livewire('/create', 'pages::checklists.create')->name('checklists.create');
            Route::livewire('/{template}/edit', 'pages::checklists.edit')->name('checklists.edit');
            Route::livewire('/tutorial', 'pages::checklists.tutorial')->name('checklists.tutorial');
        });

    });

});
