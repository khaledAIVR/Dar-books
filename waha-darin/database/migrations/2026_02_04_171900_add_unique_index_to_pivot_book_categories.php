<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniqueIndexToPivotBookCategories extends Migration
{
    public function up()
    {
        // Remove any duplicates first (book_id, category_id)
        $dups = DB::table('pivot_book_categories')
            ->selectRaw('MIN(id) as keep_id, book_id, category_id, COUNT(*) as c')
            ->groupBy('book_id', 'category_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($dups as $row) {
            DB::table('pivot_book_categories')
                ->where('book_id', $row->book_id)
                ->where('category_id', $row->category_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('pivot_book_categories', function (Blueprint $table) {
            $table->unique(['book_id', 'category_id'], 'pivot_book_categories_book_id_category_id_unique');
        });
    }

    public function down()
    {
        Schema::table('pivot_book_categories', function (Blueprint $table) {
            $table->dropUnique('pivot_book_categories_book_id_category_id_unique');
        });
    }
}

