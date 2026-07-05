<?php

namespace App\Enums;

enum UserRole: string
{
    case Manager = 'manager';
    case LeaderAdmin = 'leader_admin';
    case Checker = 'checker';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'Manager',
            self::LeaderAdmin => 'Leader / Admin',
            self::Checker => 'Checker',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Manager => 'Full system access — manage all master data, users, and reports.',
            self::LeaderAdmin => 'Manage measurement standards, add new parts, and manage users.',
            self::Checker => 'Input inspection records only. Scoped to a single process (Stamping or Welding).',
        };
    }
}
