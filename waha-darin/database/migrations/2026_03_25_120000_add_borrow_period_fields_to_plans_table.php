<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBorrowPeriodFieldsToPlansTable extends Migration
{
    /**
     * Per-period borrow caps (books per window) + annual cap in books_quota.
     * Plan 2: 2 books / 2 months from subscription start, 12 books/year.
     * Plan 3: 2 books / month from subscription start, 24 books/year.
     */
    public function up()
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('borrow_period_months')->nullable()->after('books_quota');
            $table->unsignedTinyInteger('max_books_per_period')->nullable()->after('borrow_period_months');
        });

        DB::table('plans')->where('id', 2)->update([
            'books_quota' => 12,
            'borrow_period_months' => 2,
            'max_books_per_period' => 2,
            'updated_at' => now(),
        ]);

        DB::table('plans')->where('id', 3)->update([
            'borrow_period_months' => 1,
            'max_books_per_period' => 2,
            'updated_at' => now(),
        ]);

        DB::table('plans')->whereIn('id', [4, 5])->update([
            'borrow_period_months' => null,
            'max_books_per_period' => null,
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['borrow_period_months', 'max_books_per_period']);
        });

        DB::table('plans')->where('id', 2)->update([
            'books_quota' => 6,
            'updated_at' => now(),
        ]);
    }
}
