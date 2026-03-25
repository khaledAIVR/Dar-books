<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteToPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('plans') && !Schema::hasColumn('plans', 'note')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->text('note')->nullable()->after('books_quota');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('plans', 'note')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('note');
            });
        }
    }
}
