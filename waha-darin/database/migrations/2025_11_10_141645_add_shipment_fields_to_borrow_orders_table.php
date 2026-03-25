<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShipmentFieldsToBorrowOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('borrow_orders', function (Blueprint $table) {
            $table->string('shipment_number')->nullable()->after('status');
            $table->string('shipment_status')->default('pending')->after('shipment_number');
            $table->timestamp('shipment_confirmed_at')->nullable()->after('shipment_status');
            $table->text('cancellation_note')->nullable()->after('shipment_confirmed_at');
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
                'shipment_number',
                'shipment_status',
                'shipment_confirmed_at',
                'cancellation_note',
            ]);
        });
    }
}
