<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copy core catalog tables from a MySQL database into the default DB (intended: PostgreSQL / Neon).
 * Does not replace pgloader or a full ETL; use on an empty schema after `php artisan migrate`
 * or with --force to wipe conflicting catalog (+ ratings) rows on Postgres.
 */
class ImportCatalogFromMysqlCommand extends Command
{
    protected $signature = 'data:import-catalog-from-mysql
        {--force : Truncate catalog tables (and rates) on Postgres before import}';

    protected $description = 'Copy publishers, authors, categories, books, and pivot_book_categories from mysql_source into the default connection (PostgreSQL).';

    public function handle(): int
    {
        $default = DB::connection()->getDriverName();
        if ($default !== 'pgsql') {
            $this->error('Default DB connection must be pgsql (set DB_CONNECTION=pgsql). Current: '.$default);

            return 1;
        }

        $sourceDb = (string) config('database.connections.mysql_source.database', '');
        if ($sourceDb === '') {
            $this->error('Configure mysql_source in config/database.php (MYSQL_SOURCE_DATABASE, etc.).');

            return 1;
        }

        try {
            DB::connection('mysql_source')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to mysql_source: '.$e->getMessage());

            return 1;
        }

        $target = DB::connection();
        $hasRows = (int) $target->table('publishers')->count() > 0
            || (int) $target->table('books')->count() > 0;

        if ($hasRows && !$this->option('force')) {
            $this->error('Postgres already has catalog data. Use --force to TRUNCATE catalog tables and rates, then import.');

            return 1;
        }

        if ($this->option('force')) {
            $this->warn('Truncating rates, pivot_book_categories, books, authors, publishers, categories on PostgreSQL…');
            $target->statement('TRUNCATE TABLE rates, pivot_book_categories, books, authors, publishers, categories RESTART IDENTITY CASCADE');
        }

        $this->info('Copying from MySQL (mysql_source) → PostgreSQL (default)…');

        $order = [
            'publishers',
            'authors',
            'categories',
            'books',
            'pivot_book_categories',
        ];

        foreach ($order as $table) {
            if (! Schema::connection('mysql_source')->hasTable($table)) {
                $this->warn("Skip {$table}: missing on MySQL source.");

                continue;
            }
            $count = $this->copyTable($table);
            $this->line("  {$table}: {$count} rows");
        }

        foreach ($order as $table) {
            $this->syncPostgresSequenceForTable($table);
        }

        $this->info('Done. Other tables (users, Voyager, orders, carts JSON, etc.) are unchanged — migrate those separately if needed.');

        return 0;
    }

    private function copyTable(string $table): int
    {
        $rows = DB::connection('mysql_source')->table($table)->orderBy('id')->get();
        $n = 0;
        foreach ($rows as $row) {
            $payload = json_decode(json_encode($row), true);
            if (! is_array($payload)) {
                continue;
            }
            $payload = $this->normalizeRowForPostgres($table, $payload);
            DB::table($table)->insert($payload);
            ++$n;
        }

        return $n;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeRowForPostgres(string $table, array $payload): array
    {
        if ($table === 'books' && array_key_exists('Available', $payload)) {
            $payload['Available'] = (bool) $payload['Available'];
        }

        return $payload;
    }

    private function syncPostgresSequenceForTable(string $table): void
    {
        if (! Schema::hasTable($table) || ! preg_match('/^[a-z0-9_]+$/', $table)) {
            return;
        }

        try {
            $row = DB::selectOne('SELECT pg_get_serial_sequence(?, \'id\') AS s', [$table]);
            if (! $row || empty($row->s)) {
                return;
            }
            $seqName = $row->s;
            $max = DB::table($table)->max('id');
            if ($max) {
                DB::statement('SELECT setval(?, CAST(? AS bigint), true)', [$seqName, (int) $max]);
            } else {
                DB::statement('SELECT setval(?, 1, false)', [$seqName]);
            }
        } catch (\Throwable $e) {
            // Non-serial tables — ignore
        }
    }
}
