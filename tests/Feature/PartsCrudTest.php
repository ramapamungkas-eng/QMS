<?php

use App\Models\Part;
use App\Models\Process;
use App\Models\StationType;
use Livewire\Livewire;

beforeEach(function () {
    $this->stamping = Process::factory()->create(['name' => 'Stamping']);
    $this->stationType = StationType::factory()->create(['process_id' => $this->stamping->id]);
    $this->manager = managerUser();
});

it('lists parts', function () {
    Part::factory()->count(3)->create();

    $this->actingAs($this->manager)
        ->get(route('parts.index'))
        ->assertSuccessful();
});

it('creates a part with station types', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::parts.create')
        ->set('part_number', 'PRT-TEST-001')
        ->set('part_name', 'Test Part')
        ->set('model', 'Model-X')
        ->set('variant', 'A')
        ->set('stationTypes', [(string) $this->stationType->id])
        ->call('save')
        ->assertHasNoErrors();

    $part = Part::where('part_number', 'PRT-TEST-001')->first();

    expect($part)->not->toBeNull();
    expect($part->stationTypes->pluck('id'))->toContain($this->stationType->id);
});

it('validates required fields when creating a part', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::parts.create')
        ->call('save')
        ->assertHasErrors(['part_number', 'part_name', 'stationTypes']);
});

it('rejects duplicate part numbers', function () {
    Part::factory()->create(['part_number' => 'DUPLICATE-PART']);

    Livewire::actingAs($this->manager)
        ->test('pages::parts.create')
        ->set('part_number', 'DUPLICATE-PART')
        ->set('part_name', 'Duplicate Part')
        ->set('stationTypes', [(string) $this->stationType->id])
        ->call('save')
        ->assertHasErrors(['part_number']);
});
