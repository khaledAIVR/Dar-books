<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DedupeCategoriesAndAddUniqueNameIndex extends Migration
{
    public function up()
    {
        // 1) Normalize names, then merge duplicate category rows by normalized name.
        $categories = DB::table('categories')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get();

        $canonicalByName = []; // normalized name => canonical id
        $mergeMap = [];        // duplicate id => canonical id

        foreach ($categories as $cat) {
            $normalized = $this->normalizeName($cat->name);
            if ($normalized === '') {
                continue;
            }

            if (!isset($canonicalByName[$normalized])) {
                $canonicalByName[$normalized] = (int)$cat->id;

                if ($cat->name !== $normalized) {
                    DB::table('categories')
                        ->where('id', $cat->id)
                        ->update(['name' => $normalized]);
                }
            } else {
                $mergeMap[(int)$cat->id] = $canonicalByName[$normalized];
            }
        }

        foreach ($mergeMap as $dupId => $canonicalId) {
            DB::table('pivot_book_categories')
                ->where('category_id', $dupId)
                ->update(['category_id' => $canonicalId]);

            DB::table('categories')->where('id', $dupId)->delete();
        }

        // 2) Remove duplicate pivot rows for same (book_id, category_id)
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

        // 3) Enforce uniqueness going forward (allows multiple NULLs, but names should be set)
        Schema::table('categories', function (Blueprint $table) {
            $table->unique('name', 'categories_name_unique');
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_name_unique');
        });
    }

    private function normalizeName($name): string
    {
        $n = trim((string)$name);
        if ($n === '') {
            return '';
        }
        $n = preg_replace('/\s+/u', ' ', $n);
        return $n ?? '';
    }
}

