<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Borrow checkout writes {@see OrderController::$order->use_region}; production DBs
 * created before this column existed raised SQL errors / HTTP 500 on POST /api/orders.
 */
class AddUseRegionToBorrowOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('borrow_orders', 'use_region')) {
            return;
        }

        Schema::table('borrow_orders', function (Blueprint $table) {
            $table->text('use_region')->nullable()->after('use_country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('borrow_orders', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_orders', 'use_region')) {
                $table->dropColumn('use_region');
            }
        });
    }
}
