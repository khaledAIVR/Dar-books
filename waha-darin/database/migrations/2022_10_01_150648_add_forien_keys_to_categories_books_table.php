<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForienKeysToCategoriesBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('pivot_book_categories', function (Blueprint $table) {
            $table->foreign("category_id")->references("id")
                ->on("categories")
                ->onDelete("cascade")
                ->onUpdate("cascade");

            $table->foreign("book_id")->references("id")
                ->on("books")
                ->onDelete("cascade")
                ->onUpdate("cascade");

        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pivot_book_categories', function (Blueprint $table) {
            //
            $table->dropForeign('pivot_book_categories_book_id_foreign');
            $table->dropForeign('pivot_book_categories_category_id_foreign');
        });
    }
}
