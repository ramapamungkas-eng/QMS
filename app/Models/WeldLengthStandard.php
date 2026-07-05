<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeldLengthStandard extends Model
{
    use HasFactory;

    protected $fillable = ['part_id', 'min_length', 'max_length', 'unit'];

    protected function casts(): array
    {
        return [
            'min_length' => 'decimal:2',
            'max_length' => 'decimal:2',
        ];
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
