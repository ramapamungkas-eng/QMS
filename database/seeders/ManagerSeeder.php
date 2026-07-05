<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class ManagerSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['nik' => '1000000000000001'],
            [
                'name' => 'Manager',
                'whatsapp' => null,
                'role' => UserRole::Manager,
                // Plain values on purpose — the 'hashed' cast on User handles hashing.
                // Pre-hashing here would double-hash and break login.
                'password' => 'password',
                'pin' => '123456',
            ]
        );
    }
}
