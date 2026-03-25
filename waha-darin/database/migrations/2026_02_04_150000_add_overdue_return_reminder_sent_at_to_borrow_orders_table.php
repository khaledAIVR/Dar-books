<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOverdueReturnReminderSentAtToBorrowOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('borrow_orders', function (Blueprint $table) {
            $table->timestamp('overdue_return_reminder_sent_at')
                ->nullable()
                ->after('return_confirmed_at');
        });
    }

    public function down()
    {
        Schema::table('borrow_orders', function (Blueprint $table) {
            $table->dropColumn('overdue_return_reminder_sent_at');
        });
    }
}

