<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Opscale\NovaGenbi\Models\Enums\ReportStatus;
use Opscale\NovaGenbi\Models\Repositories\ReportTemplateRepository;
use Opscale\Validations\Validatable;

/**
 * @property string $id
 * @property string $name
 * @property string $key
 * @property string|null $description
 * @property string|null $connection
 * @property string $query
 * @property array<int, mixed>|null $parameters
 * @property ReportStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ReportGeneration> $generations
 */
class ReportTemplate extends Model
{
    use HasUlids;
    use ReportTemplateRepository;
    use Validatable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'key',
        'description',
        'connection',
        'query',
        'parameters',
        'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'parameters' => 'array',
        'status' => ReportStatus::class,
    ];

    /**
     * @return array<string, array<int, string>>
     */
    final public function validationRules(): array
    {
        return [
            'name' => ['required', 'max:256'],
            'key' => ['required', 'max:64', 'unique:report_templates,key'],
            'description' => ['nullable', 'max:512'],
            'connection' => ['nullable', 'max:128'],
            'query' => ['required', 'string'],
            'parameters' => ['nullable', 'array'],
            'status' => ['required', 'in:' . implode(',', array_column(ReportStatus::cases(), 'value'))],
        ];
    }

    /**
     * @return HasMany<ReportGeneration, $this>
     */
    final public function generations(): HasMany
    {
        return $this->hasMany(ReportGeneration::class);
    }
}
