<?php

namespace App\Providers;

use App\Models\StationType;
use App\Services\ChecklistTemplateService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class InspectionRoutesProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            if (! Schema::hasTable('work_station_types')) {
                return;
            }

            $typeSlugs = app(ChecklistTemplateService::class)->allSlugs();

            foreach ($typeSlugs as $slug) {
                $stationType = StationType::where('slug', $slug)->first();

                if ($stationType === null) {
                    continue;
                }

                Route::livewire("/{$slug}", 'pages::inspections.checklist.index', ['type' => $slug])
                    ->middleware("process:{$stationType->process->name}")
                    ->name("inspections.{$slug}.index");

                Route::livewire("/{$slug}/create", 'pages::inspections.checklist.create', ['type' => $slug])
                    ->middleware("process:{$stationType->process->name}")
                    ->name("inspections.{$slug}.create");
            }
        } catch (\Exception $e) {
            return;
        }
    }
}
