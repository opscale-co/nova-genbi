<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Opscale\NovaGenbi\Models\Enums\GenerationStatus;
use Opscale\NovaGenbi\Models\Enums\ReportFormat;
use Opscale\NovaGenbi\Models\Enums\ReportStatus;
use Opscale\NovaGenbi\Models\ReportTemplate;
use Opscale\NovaGenbi\Services\Actions\GenerateReport;

beforeEach(function (): void {
    foreach (['Ada', 'Linus', 'Grace'] as $name) {
        DB::table('users')->insert([
            'name' => $name,
            'email' => strtolower($name) . '@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->template = ReportTemplate::query()->create([
        'name' => 'Users',
        'key' => 'users',
        'query' => 'SELECT name, email FROM users WHERE (:only_name IS NULL OR name = :only_name) ORDER BY name',
        'parameters' => [['name' => 'only_name', 'type' => 'string']],
        'status' => ReportStatus::Active,
    ]);
});

it('generates a csv report and exposes a working download', function (): void {
    $result = resolve(GenerateReport::class)->handle([
        'report_template' => $this->template,
        'format' => ReportFormat::Csv->value,
        'only_name' => 'Ada',
    ]);

    expect($result['_status'] ?? null)->toBe('success');

    $generation = $this->template->generations()->first();

    expect($generation)->not->toBeNull()
        ->and($generation->status)->toBe(GenerationStatus::Completed)
        ->and($generation->row_count)->toBe(1)
        ->and($generation->format)->toBe(ReportFormat::Csv);

    $this->get(route('nova-genbi.generations.download', ['reportGeneration' => $generation->id]))
        ->assertOk();
});

it('generates every row when the optional parameter is null', function (): void {
    $result = resolve(GenerateReport::class)->handle([
        'report_template' => $this->template,
        'format' => ReportFormat::Xlsx->value,
    ]);

    expect($result['_status'] ?? null)->toBe('success');
    expect($this->template->generations()->first()->row_count)->toBe(3);
});

it('fails the generation when the template is not read-only', function (): void {
    $this->template->forceFill(['query' => 'DELETE FROM users'])->save();

    $result = resolve(GenerateReport::class)->handle([
        'report_template' => $this->template,
        'format' => ReportFormat::Xlsx->value,
    ]);

    expect($result['_status'] ?? null)->toBe('fail');
});
