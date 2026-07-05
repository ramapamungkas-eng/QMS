<?php

namespace App\Models;

use App\Enums\WorkStationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkStation extends Model
{
    use HasFactory;

    protected $fillable = ['process_id', 'name', 'type'];

    protected function casts(): array
    {
        return [
            'type' => WorkStationType::class,
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }
}
