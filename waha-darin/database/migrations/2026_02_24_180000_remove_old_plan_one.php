<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveOldPlanOne extends Migration
{
    /**
     * Remove plan id 1 (old "استعارة خارجية") so only Article 2 plans 2–5 remain.
     * Only deletes if no subscription references plan_id 1.
     *
     * @return void
     */
    public function up()
    {
        $hasSubscriptions = DB::table('subscriptions')->where('plan_id', 1)->exists();
        if ($hasSubscriptions) {
            return;
        }
        DB::table('plans')->where('id', 1)->delete();
    }

    /**
     * Reverse: re-insert plan 1 for rollback (optional).
     *
     * @return void
     */
    public function down()
    {
        if (DB::table('plans')->where('id', 1)->exists()) {
            return;
        }
        $row = [
            'id' => 1,
            'name' => 'استعارة خارجية',
            'price' => 0,
            'books_quota' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('plans', 'note')) {
            $row['note'] = null;
        }
        DB::table('plans')->insert($row);
    }
}
