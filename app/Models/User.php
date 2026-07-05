<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nik',
        'whatsapp',
        'role',
        'process_id',
        'profile_pic',
        'password',
        'pin',
    ];

    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'password' => 'hashed',
            'pin' => 'hashed',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function profilePicUrl(): string
    {
        return $this->profile_pic
            ? asset('storage/'.$this->profile_pic)
            : 'https://ui-avatars.com/api/?size=256&background=random&name='.urlencode($this->name);
    }
}
