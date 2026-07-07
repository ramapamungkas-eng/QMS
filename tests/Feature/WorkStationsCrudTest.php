<?php

use App\Models\Process;
use App\Models\StationType;
use App\Models\WorkStation;
use Livewire\Livewire;

beforeEach(function () {
    $this->process = Process::factory()->create();
    $this->stationType = StationType::factory()->create(['process_id' => $this->process->id]);
    $this->manager = managerUser();
});

it('lists work stations', function () {
    WorkStation::factory()->count(3)->create();

    $this->actingAs($this->manager)
        ->get(route('work-stations.index'))
        ->assertSuccessful();
});

it('creates a work station', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::work-stations.create')
        ->set('process_id', (string) $this->process->id)
        ->set('name', 'A9')
        ->set('station_type_id', (string) $this->stationType->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(WorkStation::where('name', 'A9')->exists())->toBeTrue();
});

it('validates required fields when creating a work station', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::work-stations.create')
        ->call('save')
        ->assertHasErrors(['process_id', 'name', 'station_type_id']);
});
