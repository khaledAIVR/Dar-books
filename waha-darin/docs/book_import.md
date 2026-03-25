## Admin: Bulk import books

Open the Voyager admin panel and go to **Import Books**.

### Supported files

- `.xlsx` / `.xls` (Excel)
- `.csv` / `.txt` (CSV)

### Required columns (header row)

- `Title` (or `Name`)
- `Author`
- `Publisher`
- `Category`

### Optional columns

- `Price` (number)
- `ISBN` (string)
- `Year` (number)
- `Description` (string)
- `Available` (`true/false`, `1/0`, `yes/no`)
- `Image` (string; optional path/URL)
- `Internal Code` (string)

### Multiple categories

Put multiple categories in the `Category` cell separated by `|` or `,` or `;`.

Example:

`History|Biography`

### Notes

- If a book with the same `Title` already exists, that row is skipped.
- Missing Authors / Publishers are created automatically.
- If `Image` is empty, the system uses the default book cover image.
- Categories are normalized (trim + collapse spaces) to reduce duplicates.
- Categories use the canonical list in `config/book_import.php` (`allowed_categories`): **17 main buckets + `أخرى` (Others)**. Aliases and Arabic alif/hamza normalization apply first; then unknown labels are mapped to the **closest** main category by similarity (substring + `similar_text`). If the best score is below `category_closest_min_score`, the book is assigned to **`fallback_category_others`** (`أخرى`). Empty category cells use **`default_category`** (also `أخرى` by default).

### CLI: reset business data and import `new_dataset`

Destructive: removes orders, subscriptions, carts, favorites, catalog, and **non-admin** users (keeps Voyager users with role `admin` and/or `SUPER_ADMIN_EMAIL`). Schema unchanged.

- Dry run: `php artisan app:reset-business-data --dry-run`
- Execute: `php artisan app:reset-business-data --force` (add `--purge-public-book-images` to clear `storage/app/public/books`)

After wiping categories, re-seed allowlist categories:

- `php artisan db:seed --class=ExcelCategoriesSeeder`

Import `new_dataset/x_with_covers.xlsx` (project root) and copy covers from `new_dataset/covers/`:

- Dry run: `php artisan books:import-new-dataset --dry-run`
- Import: `php artisan books:import-new-dataset`

Options: `--excel=/path/to/file.xlsx` and `--covers-dir=/path/to/covers`.

### Seed Excel categories

Ensure all Excel categories exist in the DB:

- `php artisan db:seed --class=ExcelCategoriesSeeder`

### Make categories unique (recommended)

To merge existing duplicate categories and enforce uniqueness going forward:

- Dry-run report:
  - `php artisan categories:dedupe`
- Apply the merge:
  - `php artisan categories:dedupe --commit`
- Then run migrations (adds unique indexes for categories + pivot):
  - `php artisan migrate`

