<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BookController / CategoryController select these columns; they were missing from older
 * migrations, so a fresh deploy (e.g. Railway) threw SQL errors and the SPA showed no books.
 */
class AddInternalCodeToBooksAndColorToCategories extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('books', 'internal_code')) {
            Schema::table('books', function (Blueprint $table) {
                $table->string('internal_code')->nullable()->after('slug');
            });
        }

        if (! Schema::hasColumn('categories', 'color')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('color')->nullable()->after('slug');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('books', 'internal_code')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropColumn('internal_code');
            });
        }

        if (Schema::hasColumn('categories', 'color')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
}
