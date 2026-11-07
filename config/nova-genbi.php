<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI provider / model
    |--------------------------------------------------------------------------
    |
    | The laravel/ai provider and model the GenBI agent uses. Leave null to
    | fall back to the defaults configured in config/ai.php. The agent streams
    | with the Vercel UI Message Stream v1 protocol consumed by @ai-sdk/vue.
    |
    */
    'ai' => [
        'provider' => env('NOVA_GENBI_AI_PROVIDER'),
        'model' => env('NOVA_GENBI_AI_MODEL'),
        'max_duration' => (int) env('NOVA_GENBI_AI_MAX_DURATION', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Read-only connection
    |--------------------------------------------------------------------------
    |
    | The database connection used to run AI-generated SQL (previews, metric
    | cards and report execution). Point it at a read-only replica/user in
    | production. Leave null to use the default connection.
    |
    */
    'connection' => env('NOVA_GENBI_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Preview row limit
    |--------------------------------------------------------------------------
    |
    | How many rows the `runQuery` tool returns to the model when it previews
    | a query to validate its SQL before proposing an artifact.
    |
    */
    'preview_limit' => (int) env('NOVA_GENBI_PREVIEW_LIMIT', 25),

    /*
    |--------------------------------------------------------------------------
    | Schema context (DBML)
    |--------------------------------------------------------------------------
    |
    | The default DomainResolver returns this DBML document as the schema
    | context fed to the agent. For anything beyond a demo, bind your own
    | Opscale\NovaGenbi\Contracts\DomainResolver implementation instead.
    |
    */
    'schema' => env('NOVA_GENBI_SCHEMA'),

    /*
    |--------------------------------------------------------------------------
    | Generated report storage
    |--------------------------------------------------------------------------
    |
    | Where generated report files (.xlsx/.csv) are written. The disk must be
    | filesystem-backed (OpenSpout writes to a real path).
    |
    */
    'storage' => [
        'disk' => env('NOVA_GENBI_DISK', 'local'),
        'directory' => env('NOVA_GENBI_DIRECTORY', 'genbi-reports'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Streaming chunk size
    |--------------------------------------------------------------------------
    |
    | How many rows are pulled from the lazy cursor before being flushed to
    | the export file. Larger values trade memory for fewer iterations.
    |
    */
    'chunk' => (int) env('NOVA_GENBI_CHUNK', 1000),
];
