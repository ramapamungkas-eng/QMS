<?php

use App\Enums\InspectionStage;
use App\Jobs\GenerateReport;
use App\Models\Export;
use App\Models\InspectionRecord;
use App\Models\Part;
use App\Models\Process;
use App\Models\StationType;
use App\Models\WorkStation;
use App\Services\InspectionRecordFilter;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    seedApplication();

    $this->stamping = Process::where('name', 'Stamping')->first();
    $this->stampingType = StationType::where('slug', 'stamping')->first();
    $this->workStation = WorkStation::where('station_type_id', $this->stampingType->id)->first();
    $this->part = Part::whereHas('stationTypes', fn ($q) => $q->where('station_type_id', $this->stampingType->id))->first();

    $this->manager = managerUser();
    $this->checker = checkerUser($this->stamping);

    InspectionRecord::create([
        'part_id' => $this->part->id,
        'work_station_id' => $this->workStation->id,
        'stage' => InspectionStage::Start,
        'checker_id' => $this->checker->id,
    ]);
});

it('allows managers to view reports', function () {
    $this->actingAs($this->manager)
        ->get(route('reports.index'))
        ->assertSuccessful();
});

it('allows checkers to view reports', function () {
    $this->actingAs($this->checker)
        ->get(route('reports.index'))
        ->assertSuccessful();
});

it('scopes records to the checkers process', function () {
    $filter = new InspectionRecordFilter([], $this->checker);

    expect($filter->query()->count())->toBe(1);

    $otherProcess = Process::factory()->create();
    $otherChecker = checkerUser($otherProcess);
    $otherFilter = new InspectionRecordFilter([], $otherChecker);

    expect($otherFilter->query()->count())->toBe(0);
});

it('filters records by station type', function () {
    $filter = new InspectionRecordFilter([
        'station_type_id' => $this->stampingType->id,
    ], $this->manager);

    expect($filter->query()->count())->toBe(1);
});

it('queues a report export', function () {
    Queue::fake();

    Livewire::actingAs($this->manager)
        ->test('pages::reports.index')
        ->call('export')
        ->assertHasNoErrors();

    expect(Export::count())->toBe(1);
    Queue::assertPushed(GenerateReport::class);
});
