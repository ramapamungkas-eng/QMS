<?php

use App\Enums\UserRole;
use App\Models\Process;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->process = Process::factory()->create();
    $this->manager = managerUser();
});

it('lists users', function () {
    User::factory()->count(3)->create();

    $this->actingAs($this->manager)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee(User::first()->name);
});

it('creates a checker user', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::users.create')
        ->set('name', 'New Checker')
        ->set('nik', 'CHECK-001')
        ->set('whatsapp', '08123456789')
        ->set('role', UserRole::Checker->value)
        ->set('process_id', (string) $this->process->id)
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('pin', '123456')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('nik', 'CHECK-001')->exists())->toBeTrue();
});

it('validates required fields when creating a user', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::users.create')
        ->set('role', '')
        ->call('save')
        ->assertHasErrors(['name', 'nik', 'password', 'pin', 'role']);
});

it('rejects duplicate nik', function () {
    User::factory()->create(['nik' => 'DUPLICATE']);

    Livewire::actingAs($this->manager)
        ->test('pages::users.create')
        ->set('name', 'Another User')
        ->set('nik', 'DUPLICATE')
        ->set('role', UserRole::Checker->value)
        ->set('process_id', (string) $this->process->id)
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('pin', '123456')
        ->call('save')
        ->assertHasErrors(['nik']);
});

it('requires process for checker role', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::users.create')
        ->set('name', 'Checker Without Process')
        ->set('nik', 'NO-PROCESS')
        ->set('role', UserRole::Checker->value)
        ->set('process_id', '')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('pin', '123456')
        ->call('save')
        ->assertHasErrors(['process_id']);
});
