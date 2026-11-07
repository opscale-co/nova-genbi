<?php

declare(strict_types=1);

use Opscale\NovaGenbi\Services\Reports\CsvReportWriter;
use Opscale\NovaGenbi\Services\Reports\ExcelReportWriter;

$rows = [
    ['name' => 'Ada', 'country' => 'US'],
    ['name' => 'Linus', 'country' => 'FI'],
];

it('writes an xlsx file and reports columns and row count', function () use ($rows): void {
    $path = tempnam(sys_get_temp_dir(), 'genbi') . '.xlsx';

    $result = (new ExcelReportWriter)->write($rows, $path);

    expect($result['row_count'])->toBe(2)
        ->and($result['columns'])->toBe(['name', 'country'])
        ->and(file_exists($path))->toBeTrue()
        ->and(filesize($path))->toBeGreaterThan(0);

    @unlink($path);
});

it('writes a csv file with a header and rows', function () use ($rows): void {
    $path = tempnam(sys_get_temp_dir(), 'genbi') . '.csv';

    $result = (new CsvReportWriter)->write($rows, $path);

    $contents = (string) file_get_contents($path);

    expect($result['row_count'])->toBe(2)
        ->and($contents)->toContain('name,country')
        ->and($contents)->toContain('Ada,US');

    @unlink($path);
});
