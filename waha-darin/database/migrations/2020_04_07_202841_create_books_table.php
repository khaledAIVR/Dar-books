<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('publisher_id');
            $table->unsignedBigInteger('price');
            $table->string('title');
            $table->string('slug');
            $table->string('ISBN');
            $table->string('image');
            $table->boolean('Available');
            $table->unsignedBigInteger('year');
            $table->longText('description');
            $table->longText('pages_screenshots')->nullable();


            $table->foreign('author_id')->references('id')->on('authors')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('publisher_id')->references('id')->on('publishers')
                ->onDelete('cascade')
                ->onUpdate('cascade');

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
        Schema::dropIfExists('books');
    }
}
