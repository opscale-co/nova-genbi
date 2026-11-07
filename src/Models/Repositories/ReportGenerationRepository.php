<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Opscale\NovaGenbi\Models\Enums\GenerationStatus;
use Opscale\NovaGenbi\Models\ReportGeneration;

/**
 * Static, query-oriented helpers and status transitions mixed into the
 * ReportGeneration model.
 *
 * @phpstan-require-extends ReportGeneration
 */
trait ReportGenerationRepository
{
    /**
     * @param  Builder<ReportGeneration>  $query
     * @return Builder<ReportGeneration>
     */
    final public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', GenerationStatus::Completed->value);
    }

    final public function markProcessing(): static
    {
        $this->forceFill([
            'status' => GenerationStatus::Processing,
            'error_message' => null,
        ])->save();

        return $this;
    }

    final public function markCompleted(string $fileName, string $filePath, int $rowCount): static
    {
        $this->forceFill([
            'status' => GenerationStatus::Completed,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'row_count' => $rowCount,
            'error_message' => null,
            'generated_at' => Carbon::now(),
        ])->save();

        return $this;
    }

    final public function markFailed(string $message): static
    {
        $this->forceFill([
            'status' => GenerationStatus::Failed,
            'error_message' => $message,
            'generated_at' => Carbon::now(),
        ])->save();

        return $this;
    }

    final public function isDownloadable(): bool
    {
        return $this->status === GenerationStatus::Completed
            && $this->file_path !== null
            && $this->file_path !== '';
    }
}
