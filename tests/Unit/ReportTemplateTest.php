<?php

declare(strict_types=1);

use Opscale\NovaGenbi\Models\Enums\ReportStatus;
use Opscale\NovaGenbi\Models\ReportTemplate;

function makeTemplate(string $query, array $parameters = []): ReportTemplate
{
    return new ReportTemplate([
        'name' => 'T',
        'key' => 't',
        'query' => $query,
        'parameters' => $parameters,
        'status' => ReportStatus::Active,
    ]);
}

it('extracts named placeholders in order', function (): void {
    $template = makeTemplate('SELECT * FROM users WHERE country = :country AND created_at >= :since');

    expect($template->placeholders())->toBe(['country', 'since']);
});

it('is runnable when read-only and every placeholder is declared', function (): void {
    $template = makeTemplate(
        'SELECT * FROM users WHERE country = :country',
        [['name' => 'country', 'type' => 'string']],
    );

    expect($template->isRunnable())->toBeTrue();
});

it('is not runnable when a placeholder is undeclared', function (): void {
    $template = makeTemplate('SELECT * FROM users WHERE country = :country');

    expect($template->isRunnable())->toBeFalse();
});

it('is not runnable when the query is not read-only', function (): void {
    $template = makeTemplate('DELETE FROM users');

    expect($template->isRunnable())->toBeFalse();
});

it('normalizes the parameter schema', function (): void {
    $template = makeTemplate('SELECT 1', [['name' => 'country']]);

    $schema = $template->parameterSchema();

    expect($schema[0]['name'])->toBe('country')
        ->and($schema[0]['label'])->toBe('Country')
        ->and($schema[0]['type'])->toBe('string');
});
