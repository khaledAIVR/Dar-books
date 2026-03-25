<?php

use App\Models\Category;
use Illuminate\Database\Seeder;

class ExcelCategoriesSeeder extends Seeder
{
    public function run()
    {
        $names = (array) config('book_import.allowed_categories', []);

        foreach ($names as $name) {
            $name = preg_replace('/\s+/u', ' ', trim((string) $name));
            if (!$name) {
                continue;
            }

            $category = Category::where('name', $name)->first();
            if ($category) {
                continue;
            }

            $category = new Category();
            $category->name = $name;
            $category->slug = str_slug($name, '-');
            $category->save();
        }
    }
}

