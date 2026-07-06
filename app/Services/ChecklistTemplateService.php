<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ChecklistTemplate;
use App\Models\StationType;
use Illuminate\Support\Collection;

class ChecklistTemplateService
{
    public function forType(StationType $stationType): ?ChecklistTemplate
    {
        return ChecklistTemplate::forType($stationType)->active()->with(['sections.fields'])->first();
    }

    public function allActive(): Collection
    {
        return ChecklistTemplate::active()->with(['sections.fields'])->get();
    }

    public function routeSlug(StationType $stationType): string
    {
        return $stationType->slug;
    }

    public function fromSlug(string $slug): ?StationType
    {
        return StationType::where('slug', $slug)->first();
    }

    public function allSlugs(): array
    {
        return StationType::pluck('slug')->all();
    }

    public function accessibleTypes(): array
    {
        $user = auth()->user();

        $all = StationType::with('process')->get()->all();

        if (in_array($user->role, [UserRole::Manager, UserRole::LeaderAdmin], true)) {
            return $all;
        }

        $processName = $user->process?->name;

        return array_values(array_filter($all, fn (StationType $st) => $st->process?->name === $processName));
    }
}
