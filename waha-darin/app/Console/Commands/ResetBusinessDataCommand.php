<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Models\Role;

class ResetBusinessDataCommand extends Command
{
    protected $signature = 'app:reset-business-data
        {--dry-run : Show counts and kept admin IDs without deleting}
        {--force : Skip confirmation prompt}
        {--purge-public-book-images : Delete files under storage/app/public/books after DB wipe (before re-import)}';

    protected $description = 'Wipe orders, subscriptions, carts, catalog, and non-admin users; keep schema and Voyager admin users.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $adminRoleId = Role::query()->where('name', 'admin')->value('id');
        if ($adminRoleId === null) {
            $this->error('Voyager role "admin" not found in roles table. Aborting.');

            return 1;
        }

        $superEmail = strtolower(trim((string) config('superadmin.email', env('SUPER_ADMIN_EMAIL', ''))));

        $keepIds = User::query()
            ->where(function ($q) use ($adminRoleId, $superEmail) {
                $q->where('role_id', $adminRoleId);
                if ($superEmail !== '') {
                    $q->orWhereRaw('LOWER(email) = ?', [$superEmail]);
                }
            })
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        if ($keepIds === []) {
            $this->error('No admin users match role=admin or SUPER_ADMIN_EMAIL. Aborting to avoid locking out the app.');

            return 1;
        }

        $this->info('Kept user IDs (admin role and/or super-admin email): '.implode(', ', $keepIds));

        $counts = $this->gatherCounts();

        $this->table(array_keys($counts), [array_values($counts)]);

        if ($dryRun) {
            $this->warn('Dry run — no changes made.');

            return 0;
        }

        if (!$force && !$this->confirm('This permanently deletes the data above. Continue?')) {
            $this->warn('Aborted.');

            return 1;
        }

        DB::transaction(function () use ($keepIds) {
            DB::table('pivot_order_books')->delete();
            DB::table('borrow_orders')->delete();
            DB::table('subscriptions')->delete();
            DB::table('oauth_providers')->delete();
            DB::table('carts')->delete();
            DB::table('fav_lists')->delete();
            DB::table('pivot_book_categories')->delete();
            if (Schema::hasTable('rates')) {
                DB::table('rates')->delete();
            }
            DB::table('books')->delete();
            DB::table('authors')->delete();
            DB::table('publishers')->delete();
            DB::table('categories')->delete();

            User::query()->whereNotIn('id', $keepIds)->delete();
        });

        if (Schema::hasTable('password_resets')) {
            DB::table('password_resets')->delete();
        }

        $this->info('Business data wiped. Admin users preserved.');

        if ($this->option('purge-public-book-images')) {
            $disk = Storage::disk('public');
            $prefix = 'books';
            if ($disk->exists($prefix)) {
                foreach ($disk->allFiles($prefix) as $path) {
                    $disk->delete($path);
                }
                $this->info("Deleted files under storage/app/public/{$prefix}.");
            }
        }

        return 0;
    }

    /**
     * @return array<string, int>
     */
    private function gatherCounts(): array
    {
        $tables = [
            'pivot_order_books',
            'borrow_orders',
            'subscriptions',
            'oauth_providers',
            'carts',
            'fav_lists',
            'pivot_book_categories',
            'books',
            'authors',
            'publishers',
            'categories',
            'rates',
            'users',
        ];

        $out = [];
        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                $out[$t] = (int) DB::table($t)->count();
            }
        }

        return $out;
    }
}
