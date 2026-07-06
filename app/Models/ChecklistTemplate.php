<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    protected $table = 'inspection_checklist_templates';

    protected $fillable = [
        'station_type_id',
        'name',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<StationType, $this> */
    public function stationType(): BelongsTo
    {
        return $this->belongsTo(StationType::class);
    }

    /** @return HasMany<ChecklistSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(ChecklistSection::class, 'template_id')->orderBy('order');
    }

    /**
     * @param  Builder<ChecklistTemplate>  $query
     * @return Builder<ChecklistTemplate>
     */
    public function scopeForType(Builder $query, StationType $stationType): Builder
    {
        return $query->where('station_type_id', $stationType->id);
    }

    /**
     * @param  Builder<ChecklistTemplate>  $query
     * @return Builder<ChecklistTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
