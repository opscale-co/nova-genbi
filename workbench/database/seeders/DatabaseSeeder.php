<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Opscale\NovaGenbi\Models\Enums\MetricType;
use Opscale\NovaGenbi\Models\Enums\ReportStatus;
use Opscale\NovaGenbi\Models\GenbiDashboard;
use Opscale\NovaGenbi\Models\Metric;
use Opscale\NovaGenbi\Models\ReportTemplate;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->seedReportTemplates();
        $this->seedMetrics();
    }

    private function seedUsers(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@laravel.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password'), 'country' => 'US'],
        );

        if (User::query()->count() > 10) {
            return;
        }

        $countries = ['US', 'MX', 'ES', 'AR', 'CO', 'BR'];

        foreach ($countries as $index => $country) {
            User::factory()->count(3 + $index)->create(['country' => $country]);
        }
    }

    private function seedReportTemplates(): void
    {
        if (ReportTemplate::query()->where('key', 'users-by-country')->exists()) {
            return;
        }

        ReportTemplate::query()->create([
            'name' => 'Users by country',
            'key' => 'users-by-country',
            'description' => 'Lists registered users, optionally filtered by country.',
            'query' => <<<'SQL'
                SELECT name, email, country, created_at
                FROM users
                WHERE (:country IS NULL OR country = :country)
                ORDER BY country, name
                SQL,
            'parameters' => [
                [
                    'name' => 'country',
                    'label' => 'Country',
                    'type' => 'string',
                    'rules' => ['nullable', 'string', 'max:2'],
                ],
            ],
            'status' => ReportStatus::Active,
        ]);
    }

    private function seedMetrics(): void
    {
        if (Metric::query()->where('title', 'Total users')->exists()) {
            return;
        }

        $dashboard = GenbiDashboard::query()->firstOrCreate(
            ['key' => 'overview'],
            ['name' => 'Overview', 'enabled' => true],
        );

        Metric::query()->create([
            'genbi_dashboard_id' => $dashboard->id,
            'title' => 'Total users',
            'type' => MetricType::Value,
            'query' => 'SELECT COUNT(*) AS total FROM users',
            'enabled' => true,
        ]);

        Metric::query()->create([
            'genbi_dashboard_id' => $dashboard->id,
            'title' => 'Users by country',
            'type' => MetricType::Partition,
            'query' => 'SELECT country, COUNT(*) AS total FROM users GROUP BY country',
            'enabled' => true,
        ]);
    }
}
