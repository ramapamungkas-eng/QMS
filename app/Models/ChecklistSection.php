<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistSection extends Model
{
    protected $table = 'inspection_checklist_sections';

    protected $fillable = [
        'template_id',
        'label',
        'order',
        'allow_multiple',
        'source_type',
    ];

    protected function casts(): array
    {
        return [
            'allow_multiple' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ChecklistField::class, 'section_id')->orderBy('order');
    }
}
