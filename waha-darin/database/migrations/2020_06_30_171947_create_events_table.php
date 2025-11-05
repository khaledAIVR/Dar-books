<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug');
            $table->unsignedBigInteger('price');

            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->string('location');
            $table->string('map_location_embed_link');

            $table->string('contact_phone');
            $table->string('contact_mail');

            $table->string('image');
            $table->longText('description')->nullable();
            $table->longText('short_description')->nullable();



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
        Schema::dropIfExists('events');
    }
}
