<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Opscale\NovaGenbi\Models\Enums\GenerationStatus;
use Opscale\NovaGenbi\Models\Enums\ReportFormat;
use Opscale\NovaGenbi\Models\Repositories\ReportGenerationRepository;
use Opscale\Validations\Validatable;

/**
 * @property string $id
 * @property string $report_template_id
 * @property array<string, mixed>|null $parameters
 * @property ReportFormat $format
 * @property GenerationStatus $status
 * @property string|null $file_name
 * @property string|null $file_path
 * @property int|null $row_count
 * @property string|null $error_message
 * @property Carbon|null $generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ReportTemplate $reportTemplate
 */
class ReportGeneration extends Model
{
    use HasUlids;
    use ReportGenerationRepository;
    use Validatable;

    /** @var list<string> */
    protected $fillable = [
        'report_template_id',
        'parameters',
        'format',
        'status',
        'file_name',
        'file_path',
        'row_count',
        'error_message',
        'generated_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'parameters' => 'array',
        'format' => ReportFormat::class,
        'status' => GenerationStatus::class,
        'row_count' => 'integer',
        'generated_at' => 'datetime',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    final public function validationRules(): array
    {
        return [
            'report_template_id' => ['required', 'exists:report_templates,id'],
            'parameters' => ['nullable', 'array'],
            'format' => ['required', 'in:' . implode(',', array_column(ReportFormat::cases(), 'value'))],
            'status' => ['required', 'in:' . implode(',', array_column(GenerationStatus::cases(), 'value'))],
            'file_name' => ['nullable', 'max:256'],
            'file_path' => ['nullable', 'max:2048'],
            'row_count' => ['nullable', 'integer', 'min:0'],
            'error_message' => ['nullable', 'string'],
        ];
    }

    /**
     * @return BelongsTo<ReportTemplate, $this>
     */
    final public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }
}
