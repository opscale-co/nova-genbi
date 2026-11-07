<?php

declare(strict_types=1);

use Opscale\NovaGenbi\Services\Sql\ReadOnlySqlGuard;

beforeEach(function (): void {
    $this->guard = new ReadOnlySqlGuard;
});

it('accepts a plain SELECT', function (): void {
    expect($this->guard->isReadOnly('SELECT id, name FROM users'))->toBeTrue();
});

it('accepts a WITH ... SELECT (CTE)', function (): void {
    expect($this->guard->isReadOnly('WITH t AS (SELECT 1 AS n) SELECT n FROM t'))->toBeTrue();
});

it('tolerates a single trailing semicolon', function (): void {
    expect($this->guard->isReadOnly('SELECT 1;'))->toBeTrue();
});

it('rejects mutating statements', function (string $sql): void {
    expect($this->guard->isReadOnly($sql))->toBeFalse();
})->with([
    'update' => 'UPDATE users SET name = "x"',
    'delete' => 'DELETE FROM users',
    'insert' => 'INSERT INTO users (name) VALUES ("x")',
    'drop' => 'DROP TABLE users',
    'alter' => 'ALTER TABLE users ADD COLUMN x int',
    'truncate' => 'TRUNCATE users',
    'select into' => 'SELECT * INTO backup FROM users',
]);

it('rejects stacked statements', function (): void {
    expect($this->guard->isReadOnly('SELECT 1; DROP TABLE users'))->toBeFalse();
});

it('rejects an empty query', function (): void {
    expect($this->guard->isReadOnly('   '))->toBeFalse();
});

it('throws from assert() on unsafe SQL', function (): void {
    $this->guard->assert('DELETE FROM users');
})->throws(InvalidArgumentException::class);

it('wraps a query in a bounded preview', function (): void {
    $preview = $this->guard->toPreview('SELECT * FROM users;', 10);

    expect($preview)->toContain('SELECT * FROM (SELECT * FROM users) AS genbi_preview LIMIT 10');
});
