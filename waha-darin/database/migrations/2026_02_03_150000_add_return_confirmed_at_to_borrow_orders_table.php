<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReturnConfirmedAtToBorrowOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('borrow_orders', function (Blueprint $table) {
            $table->timestamp('return_confirmed_at')->nullable()->after('return_shipment_added_at');
        });
    }

    public function down()
    {
        Schema::table('borrow_orders', function (Blueprint $table) {
            $table->dropColumn('return_confirmed_at');
        });
    }
}

