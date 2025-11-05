<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBorrowOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('borrow_orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            //  User Data stored in the order for future reference
            $table->unsignedBigInteger('user_id');
            $table->mediumText('user_name');
            $table->mediumText('user_phone');
            $table->longText('user_address_line_one');
            $table->longText('user_address_line_two')->nullable();
            $table->text('use_country');
            $table->text('use_zipCode');

            //  Borrow information
//            $table->json('books');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('status');

            // This values aren't used yet, but will be once we start selling books
            $table->float('sub_total')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->float('total')->default(0);
            $table->boolean('checkout')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('borrow_orders');
    }
}
