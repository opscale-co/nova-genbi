<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Opscale\NovaGenbi\Models\Enums\ReportFormat;
use Opscale\NovaGenbi\Models\ReportGeneration;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController
{
    /**
     * Stream the stored export file for a completed generation.
     *
     * The generation is resolved explicitly (rather than via implicit route
     * model binding) so the endpoint behaves the same regardless of whether
     * the host route group applies SubstituteBindings.
     */
    final public function download(string $reportGeneration): StreamedResponse
    {
        $generation = ReportGeneration::query()->find($reportGeneration);

        abort_unless($generation instanceof ReportGeneration && $generation->isDownloadable(), 404);

        /** @var string $disk */
        $disk = config('nova-genbi.storage.disk', 'local');
        /** @var string $path */
        $path = (string) $generation->file_path;

        abort_unless(Storage::disk($disk)->exists($path), 404);

        $format = $generation->format instanceof ReportFormat ? $generation->format : ReportFormat::Xlsx;
        $fileName = $generation->file_name ?? ('report.' . $format->extension());

        return Storage::disk($disk)->download($path, $fileName, [
            'Content-Type' => $format->contentType(),
        ]);
    }
}
