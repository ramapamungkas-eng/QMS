<?php

use App\Models\Process;
use App\Models\StationType;

beforeEach(function () {
    $this->stamping = Process::factory()->create(['name' => 'Stamping']);
    $this->welding = Process::factory()->create(['name' => 'Welding']);

    StationType::factory()->create([
        'process_id' => $this->stamping->id,
        'slug' => 'stamping',
    ]);

    StationType::factory()->create([
        'process_id' => $this->welding->id,
        'slug' => 'station-spot',
    ]);
});

it('allows managers to access admin routes', function () {
    $user = managerUser();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('hardware.index'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('parts.index'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('work-stations.index'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('checklists.index'))
        ->assertSuccessful();
});

it('allows leader admins to access admin routes', function () {
    $user = leaderAdminUser();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertSuccessful();
});

it('forbids checkers from admin routes', function () {
    $user = checkerUser($this->stamping);

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});

it('allows checkers to access inspections for their process', function () {
    $user = checkerUser($this->stamping);

    $this->actingAs($user)
        ->get(route('inspections.stamping.index'))
        ->assertSuccessful();
});

it('forbids checkers from accessing inspections for other processes', function () {
    $user = checkerUser($this->stamping);

    $this->actingAs($user)
        ->get(route('inspections.station-spot.index'))
        ->assertForbidden();
});

it('allows managers to access all inspection routes', function () {
    $user = managerUser();

    $this->actingAs($user)
        ->get(route('inspections.stamping.index'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('inspections.station-spot.index'))
        ->assertSuccessful();
});
