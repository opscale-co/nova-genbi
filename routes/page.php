<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| nova-genbi tool page
|--------------------------------------------------------------------------
|
| Mounted via Nova::router() by the package provider (hasNovaPageRoute), so
| it renders inside the standard Nova layout at /nova/nova-genbi. The
| component name matches Nova.inertia('NovaGenbi', ...) in resources/js/tool.js.
|
*/

Route::get('/', fn () => Inertia::render('NovaGenbi'))
    ->name('nova-genbi.index');
