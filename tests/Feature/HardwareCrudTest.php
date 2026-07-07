<?php

use App\Models\HardwareType;
use Livewire\Livewire;

beforeEach(function () {
    $this->manager = managerUser();
});

it('lists hardware types', function () {
    HardwareType::factory()->count(3)->create();

    $this->actingAs($this->manager)
        ->get(route('hardware.index'))
        ->assertSuccessful();
});

it('creates a hardware type', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::hardware.create')
        ->set('part_number', 'HT-TEST-001')
        ->set('part_name', 'Test Nut')
        ->call('save')
        ->assertHasNoErrors();

    expect(HardwareType::where('part_number', 'HT-TEST-001')->exists())->toBeTrue();
});

it('validates required fields when creating hardware', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::hardware.create')
        ->call('save')
        ->assertHasErrors(['part_number', 'part_name']);
});

it('rejects duplicate hardware part numbers', function () {
    HardwareType::factory()->create(['part_number' => 'DUPLICATE-HT']);

    Livewire::actingAs($this->manager)
        ->test('pages::hardware.create')
        ->set('part_number', 'DUPLICATE-HT')
        ->set('part_name', 'Duplicate Hardware')
        ->call('save')
        ->assertHasErrors(['part_number']);
});
