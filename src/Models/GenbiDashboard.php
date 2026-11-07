<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Opscale\Validations\Validatable;

/**
 * A named group of metrics rendered together as one native Nova dashboard.
 *
 * @property string $id
 * @property string $name
 * @property string $key
 * @property string|null $description
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Metric> $metrics
 */
class GenbiDashboard extends Model
{
    use HasUlids;
    use Validatable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'key',
        'description',
        'enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    final public function validationRules(): array
    {
        return [
            'name' => ['required', 'max:256'],
            'key' => ['required', 'max:64', 'unique:genbi_dashboards,key'],
            'description' => ['nullable', 'max:512'],
            'enabled' => ['boolean'],
        ];
    }

    /**
     * @return HasMany<Metric, $this>
     */
    final public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }
}
