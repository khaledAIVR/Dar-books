## Cover fetcher + human review (macOS M1)

This small toolchain does 3 things:

1) **Download candidate covers** from Google Images (opens a real browser, downloads first image).
2) **Human review** via a local web UI (Accept/Delete).
3) **Write a NEW XLSX** updating `AA` (`has_image`) + `AB` (`cover_name`) for accepted covers.

### Paths used (this repo)

- **Input XLSX** (source of truth):  
  `waha-darin/storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED_WITH_COVERS.xlsx`
- **Download folder** (new covers):  
  `waha-darin/storage/app/public/book-covers-by-title/new-covers/`
- **Mapping TSV** (created/updated):  
  `waha-darin/storage/app/public/book-covers-by-title/new-covers/_new_covers_mapping.tsv`

### Setup (recommended: venv)

From `waha-darin/`:

```bash
python3 -m venv .venv
source .venv/bin/activate
python -m pip install -U pip
python -m pip install -r scripts/cover_fetcher/requirements.txt
python -m playwright install chromium
```

### 1) Download covers (for rows with no cover)

```bash
python scripts/cover_fetcher/cover_fetch.py \
  --xlsx "storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED_WITH_COVERS.xlsx" \
  --sheet "Sheet1" \
  --out-dir "storage/app/public/book-covers-by-title/new-covers" \
  --mapping "storage/app/public/book-covers-by-title/new-covers/_new_covers_mapping.tsv" \
  --limit 50
```

Notes:
- This opens a real browser window. If Google shows consent/captcha, solve it once and continue.
- Re-running is safe: it won’t re-download rows already present in the mapping TSV.

### 2) Human review (Accept/Delete)

```bash
python scripts/cover_fetcher/review_app.py \
  --out-dir "storage/app/public/book-covers-by-title/new-covers" \
  --mapping "storage/app/public/book-covers-by-title/new-covers/_new_covers_mapping.tsv"
```

Then open `http://127.0.0.1:5005`.

### 3) Write a NEW XLSX with accepted covers applied

```bash
python scripts/cover_fetcher/update_xlsx.py \
  --xlsx-in "storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED_WITH_COVERS.xlsx" \
  --sheet "Sheet1" \
  --mapping "storage/app/public/book-covers-by-title/new-covers/_new_covers_mapping.tsv" \
  --xlsx-out "storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED_WITH_COVERS_PLUS_NEW.xlsx"
```

This writes only:
- `AA(row) = 1`
- `AB(row) = <saved_filename>`

### Optional: status summary

```bash
python scripts/cover_fetcher/report_status.py \
  --xlsx "storage/app/public/book-covers-by-title/Darbooks_final_books_PLUS_910_DEDUPED_WITH_COVERS.xlsx" \
  --sheet "Sheet1" \
  --mapping "storage/app/public/book-covers-by-title/new-covers/_new_covers_mapping.tsv" \
  --out-dir "storage/app/public/book-covers-by-title/new-covers"
```

