<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-assert per-period caps vs annual books_quota.
 * Some DBs may have max_books_per_period missing or wrongly set to the annual total (12/24),
 * which would allow borrowing the whole year at once.
 */
class EnsureExternalPlanPerPeriodBookCaps extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        $hasPeriod = Schema::hasColumn('plans', 'borrow_period_months')
            && Schema::hasColumn('plans', 'max_books_per_period');

        if ($hasPeriod) {
            DB::table('plans')->where('id', 2)->update([
                'books_quota' => 12,
                'borrow_period_months' => 2,
                'max_books_per_period' => 2,
                'updated_at' => now(),
            ]);

            DB::table('plans')->where('id', 3)->update([
                'books_quota' => 24,
                'borrow_period_months' => 1,
                'max_books_per_period' => 2,
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Intentionally empty: do not revert corrected caps.
    }
}
