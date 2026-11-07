<?php

declare(strict_types=1);

use Opscale\NovaGenbi\Models\Enums\MetricType;
use Opscale\NovaGenbi\Models\GenbiDashboard;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Models\ReportTemplate;

it('persists an accepted report proposal', function (): void {
    $response = $this->postJson('/nova-vendor/nova-genbi/persist', [
        'kind' => 'report',
        'name' => 'Inactive customers',
        'description' => 'Customers with no recent activity',
        'sql' => 'SELECT id, name FROM users WHERE country = :country',
        'parameters' => [['name' => 'country', 'type' => 'string']],
    ]);

    $response->assertOk()->assertJson(['ok' => true, 'type' => 'report']);

    $template = ReportTemplate::query()->firstWhere('name', 'Inactive customers');

    expect($template)->not->toBeNull()
        ->and($template->key)->toBe('inactive-customers')
        ->and($template->parameters[0]['name'])->toBe('country');
});

it('rejects a report proposal whose SQL is not read-only', function (): void {
    $this->postJson('/nova-vendor/nova-genbi/persist', [
        'kind' => 'report',
        'name' => 'Nope',
        'sql' => 'DELETE FROM users',
    ])->assertStatus(422);

    expect(ReportTemplate::query()->count())->toBe(0);
});

it('persists an accepted metric proposal and creates its dashboard', function (): void {
    $response = $this->postJson('/nova-vendor/nova-genbi/persist', [
        'kind' => 'metric',
        'title' => 'Total users',
        'type' => MetricType::Value->value,
        'sql' => 'SELECT COUNT(*) FROM users',
        'dashboard' => 'Overview',
    ]);

    $response->assertOk()->assertJson(['ok' => true, 'type' => 'metric']);

    $dashboard = GenbiDashboard::query()->firstWhere('key', 'overview');
    $metric = Metric::query()->firstWhere('title', 'Total users');

    expect($dashboard)->not->toBeNull()
        ->and($metric)->not->toBeNull()
        ->and($metric->genbi_dashboard_id)->toBe($dashboard->id)
        ->and($metric->type)->toBe(MetricType::Value);
});

it('rejects a metric proposal whose SQL is not read-only', function (): void {
    $this->postJson('/nova-vendor/nova-genbi/persist', [
        'kind' => 'metric',
        'title' => 'Bad',
        'type' => 'value',
        'sql' => 'UPDATE users SET name = "x"',
    ])->assertStatus(422);

    expect(Metric::query()->count())->toBe(0);
});
