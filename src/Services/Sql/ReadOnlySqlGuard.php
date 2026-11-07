<?php

declare(strict_types=1);

namespace Opscale\NovaGenbi\Services\Sql;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Central gate for every piece of AI-generated SQL in the package.
 *
 * All dynamic SQL — the `runQuery` preview tool, persisted report templates,
 * and the dynamic metric cards — flows through here before it ever touches a
 * connection. The guard only allows a single read-only statement: a SELECT or
 * a WITH ... SELECT, with no stacked statements and no DDL/DML keywords.
 *
 * This is deliberately conservative (an allow-list of statement shapes plus a
 * deny-list of mutating keywords). It is NOT a substitute for connecting with
 * a least-privileged, read-only database user in production — configure
 * `nova-genbi.connection` to point at one.
 */
class ReadOnlySqlGuard
{
    /**
     * Keywords that must never appear in a read-only query. Matched on word
     * boundaries, case-insensitively.
     */
    private const FORBIDDEN = [
        'insert', 'update', 'delete', 'drop', 'alter', 'truncate', 'create',
        'replace', 'rename', 'grant', 'revoke', 'merge', 'call', 'exec',
        'execute', 'into', 'attach', 'detach', 'pragma', 'vacuum', 'lock',
        'commit', 'rollback', 'savepoint', 'set', 'copy', 'load', 'handler',
    ];

    /**
     * Whether the statement is a single read-only SELECT/WITH query.
     */
    final public function isReadOnly(string $sql): bool
    {
        $query = $this->normalize($sql);

        if ($query === '') {
            return false;
        }

        // Must open with SELECT or WITH.
        if (preg_match('/^(select|with)\b/i', $query) !== 1) {
            return false;
        }

        // Reject stacked statements (a single trailing `;` is tolerated).
        if (preg_match('/;\s*\S/', $query) === 1) {
            return false;
        }

        foreach (self::FORBIDDEN as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $query) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Throw unless the statement is read-only.
     *
     * @throws InvalidArgumentException
     */
    final public function assert(string $sql): void
    {
        if (! $this->isReadOnly($sql)) {
            throw new InvalidArgumentException(
                'The query is not a single read-only SELECT/WITH statement.'
            );
        }
    }

    /**
     * The connection AI-generated SQL runs against (a read-only replica/user
     * when `nova-genbi.connection` is configured; the default otherwise).
     */
    final public function connection(?string $name = null): ConnectionInterface
    {
        $connection = $name
            ?? (is_string(config('nova-genbi.connection')) ? config('nova-genbi.connection') : null);

        return $connection !== null && $connection !== ''
            ? DB::connection($connection)
            : DB::connection();
    }

    /**
     * Wrap a validated query in a bounded preview so the model can inspect a
     * sample without pulling the whole result set.
     */
    final public function toPreview(string $sql, int $limit): string
    {
        $inner = rtrim($this->normalize($sql), ';');

        return sprintf('SELECT * FROM (%s) AS genbi_preview LIMIT %d', $inner, max(1, $limit));
    }

    private function normalize(string $sql): string
    {
        return trim($sql);
    }
}
