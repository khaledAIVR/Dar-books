<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryAndReturnFieldsToBorrowOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('borrow_orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('shipment_confirmed_at');
            $table->string('return_shipment_number')->nullable()->after('delivered_at');
            $table->timestamp('return_shipment_added_at')->nullable()->after('return_shipment_number');
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
            $table->dropColumn([
                'delivered_at',
                'return_shipment_number',
                'return_shipment_added_at',
            ]);
        });
    }
}

