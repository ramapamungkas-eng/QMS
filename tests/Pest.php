<?php

use App\Enums\UserRole;
use App\Models\Process;
use App\Models\User;
use Database\Seeders\ChecklistTemplateSeeder;
use Database\Seeders\ManagerSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| expect() function gives you access to a set of expectations methods that you can use to
| assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Test Helpers
|--------------------------------------------------------------------------
*/

function seedApplication(): void
{
    (new MasterDataSeeder)->run();
    (new ManagerSeeder)->run();
    (new ChecklistTemplateSeeder)->run();
}

function managerUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => UserRole::Manager,
    ], $overrides));
}

function leaderAdminUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => UserRole::LeaderAdmin,
    ], $overrides));
}

function checkerUser(?Process $process = null, array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => UserRole::Checker,
        'process_id' => $process?->id,
    ], $overrides));
}
