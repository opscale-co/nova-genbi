<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Opscale\NovaGenbi\Http\Controllers\ChatController;
use Opscale\NovaGenbi\Http\Controllers\PersistController;
use Opscale\NovaGenbi\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| nova-genbi XHR routes
|--------------------------------------------------------------------------
|
| Mounted under nova-vendor/nova-genbi by the package provider (see
| PackageServiceProvider::configurePackage → hasNovaApiRoute). They inherit
| Nova's panel authentication from the `nova` middleware group.
|
*/

// Streaming chat endpoint that drives report/dashboard creation.
Route::post('ai/chat', ChatController::class)
    ->name('nova-genbi.ai.chat');

// Persist an accepted proposal (report template or metric) after validating
// its SQL server-side with the read-only guard.
Route::post('persist', PersistController::class)
    ->name('nova-genbi.persist');

// Stream a generated report file.
Route::get('generations/{reportGeneration}/download', [ReportController::class, 'download'])
    ->name('nova-genbi.generations.download');
