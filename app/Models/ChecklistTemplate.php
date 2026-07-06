<?php

namespace App\Models;

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

    public function stationType(): BelongsTo
    {
        return $this->belongsTo(StationType::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ChecklistSection::class, 'template_id')->orderBy('order');
    }

    public function scopeForType($query, StationType $stationType)
    {
        return $query->where('station_type_id', $stationType->id);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
