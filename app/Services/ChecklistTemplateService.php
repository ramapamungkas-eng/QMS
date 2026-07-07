<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ChecklistTemplate;
use App\Models\StationType;
use App\Models\User;
use Illuminate\Support\Collection;

class ChecklistTemplateService
{
    public function forType(StationType $stationType): ?ChecklistTemplate
    {
        return ChecklistTemplate::forType($stationType)->active()->with(['sections.fields'])->first();
    }

    /** @return Collection<int, ChecklistTemplate> */
    public function allActive(): Collection
    {
        return ChecklistTemplate::active()->with(['sections.fields'])->get();
    }

    public function fromSlug(string $slug): ?StationType
    {
        return StationType::where('slug', $slug)->first();
    }

    /** @return array<int, string> */
    public function allSlugs(): array
    {
        return StationType::pluck('slug')->all();
    }

    /** @return array<int, StationType> */
    public function accessibleTypes(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $all = StationType::with('process')->get()->all();

        if (in_array($user->role, [UserRole::Manager, UserRole::LeaderAdmin], true)) {
            return $all;
        }

        $processName = $user->process?->name;

        return array_values(array_filter($all, fn (StationType $st) => $st->process?->name === $processName));
    }
}
