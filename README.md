# Nova GenBI

Generative BI for Laravel Nova. Chat with an AI that reads your database schema,
writes **read-only SQL**, and builds:

- **Dashboards** — native Nova metric cards (Value, Trend, Partition, Progress, Table)
  generated from the metrics the assistant proposes.
- **Reports** — parametrized SQL templates you run and export to **Excel / CSV**
  (streamed with OpenSpout, memory-bounded over millions of rows).

The chat UI is built with the Vercel AI SDK (`@ai-sdk/vue`) and streams over the
UI Message Stream v1 protocol produced by [`laravel/ai`](https://laravel.com/ai).

## How it works

1. You open the **Gen BI** tool page and ask for a report or a metric in natural language.
2. The agent is given your schema as **DBML** (via a `DomainResolver`) and writes SQL.
3. It can **preview** a query with the `runQuery` tool (read-only, auto-limited) to validate it.
4. It **proposes** a report or a metric as a streamed tool call; you review the SQL and
   **Accept & save** or **Discard**. Nothing is written until you accept.
5. Accepted reports become `ReportTemplate` records (run + export via Nova actions);
   accepted metrics become native cards on a Gen BI dashboard.

Every piece of AI-generated SQL passes the **`ReadOnlySqlGuard`** before it ever runs:
a single `SELECT`/`WITH` statement, no stacked statements, no DDL/DML keywords. Point
`nova-genbi.connection` at a least-privileged read-only database user in production.

## Requirements

- PHP 8.3+, Laravel Nova 5.7+
- `laravel/ai ^0.9` with a configured provider (OpenAI, Anthropic, …)

> **Protocol pin:** keep `laravel/ai ^0.9` ↔ `@ai-sdk/vue 4.0.x` / `ai 7.0.x` together;
> they share UI Message Stream v1. Bump both sides at once.

## Installation

```bash
composer require opscale-co/nova-genbi
php artisan vendor:publish --tag=nova-genbi-config
```

`laravel/ai` publishes its own migrations (conversation memory) — publish and migrate them too:

```bash
php artisan vendor:publish --tag=laravel-ai-migrations
php artisan migrate
```

Set your AI provider credentials (e.g. `OPENAI_API_KEY`) and optionally:

```dotenv
NOVA_GENBI_AI_PROVIDER=anthropic
NOVA_GENBI_AI_MODEL=claude-sonnet-5
NOVA_GENBI_CONNECTION=reporting_readonly
```

Register the tool in `app/Providers/NovaServiceProvider.php`:

```php
public function tools(): array
{
    return [new \Opscale\NovaGenbi\Tool\GenBI];
}
```

## Schema context (required for good SQL)

The agent only writes SQL for the tables you describe. Bind a `DomainResolver`
that returns your domain as DBML:

```php
use Opscale\NovaGenbi\Contracts\DomainResolver;

$this->app->singleton(DomainResolver::class, MyDomainResolver::class);
```

```php
final class MyDomainResolver implements DomainResolver
{
    public function resolve(): string
    {
        return <<<'DBML'
        Table orders {
          id integer [primary key]
          customer_id integer [note: 'FK to customers.id']
          total decimal [note: 'Order total in cents.']
          created_at timestamp [note: 'When the order was placed.']
          Note: 'One row per placed order.'
        }
        DBML;
    }
}
```

Rich table/column **notes** matter: they carry the business meaning the model needs.
Without a resolver, the default reads the `nova-genbi.schema` config value.

## Development

```bash
composer install
npm install && npm run prod   # compile the Vue chat bundle
composer build                # build the demo workbench Nova app (migrations + seed)
composer serve                # http://127.0.0.1:8000/nova  (admin@laravel.com / password)
composer test                 # Pest unit + feature tests
```

The workbench ships a demo `DomainResolver`, a seeded report template, and two metrics
on an "Overview" dashboard.

## License

MIT
