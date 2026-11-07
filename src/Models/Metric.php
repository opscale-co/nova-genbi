<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Opscale\NovaGenbi\Models\Enums\MetricType;
use Opscale\Validations\Validatable;

/**
 * A stored metric spec rendered as a native Nova metric card.
 *
 * @property string $id
 * @property string|null $genbi_dashboard_id
 * @property string $title
 * @property MetricType $type
 * @property string|null $connection
 * @property string $query
 * @property array<string, mixed>|null $options
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read GenbiDashboard|null $dashboard
 */
class Metric extends Model
{
    use HasUlids;
    use Validatable;

    /** @var list<string> */
    protected $fillable = [
        'genbi_dashboard_id',
        'title',
        'type',
        'connection',
        'query',
        'options',
        'enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => MetricType::class,
        'options' => 'array',
        'enabled' => 'boolean',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    final public function validationRules(): array
    {
        return [
            'genbi_dashboard_id' => ['nullable', 'exists:genbi_dashboards,id'],
            'title' => ['required', 'max:256'],
            'type' => ['required', 'in:' . implode(',', array_column(MetricType::cases(), 'value'))],
            'connection' => ['nullable', 'max:128'],
            'query' => ['required', 'string'],
            'options' => ['nullable', 'array'],
            'enabled' => ['boolean'],
        ];
    }

    /**
     * @return BelongsTo<GenbiDashboard, $this>
     */
    final public function dashboard(): BelongsTo
    {
        return $this->belongsTo(GenbiDashboard::class, 'genbi_dashboard_id');
    }
}
