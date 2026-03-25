#!/usr/bin/env python3
"""
Merge:
  - _covers_vs_excel_colA_fuzzy_matched_ge_97.tsv (6-column format)
  - _covers_vs_excel_colA_same_but_lt_97.tsv (superset columns; we take first 6)

Output a combined 6-column TSV.

Usage (from waha-darin/):
  python3 scripts/merge_cover_match_reports.py \
    --matched storage/app/public/book-covers-by-title/_covers_vs_excel_colA_fuzzy_matched_ge_97.tsv \
    --same storage/app/public/book-covers-by-title/_covers_vs_excel_colA_same_but_lt_97.tsv \
    --out storage/app/public/book-covers-by-title/_covers_vs_excel_colA_fuzzy_matched_ge_97_plus_llm.tsv
"""

from __future__ import annotations

import argparse
from pathlib import Path


def tsv_unescape(s: str) -> str:
    return s.replace("\\t", "\t").replace("\\r", "\r").replace("\\n", "\n")


def tsv_escape(s: str) -> str:
    return str(s).replace("\t", "\\t").replace("\r", "\\r").replace("\n", "\\n")


def read_tsv(path: Path) -> tuple[list[str], list[list[str]]]:
    lines = path.read_text(encoding="utf-8", errors="replace").splitlines()
    lines = [ln for ln in lines if ln.strip() != ""]
    if not lines:
        raise SystemExit(f"Empty TSV: {path}")
    header = lines[0].split("\t")
    rows: list[list[str]] = []
    for ln in lines[1:]:
        parts = ln.split("\t")
        rows.append([tsv_unescape(p) for p in parts])
    return header, rows


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--matched", required=True)
    ap.add_argument("--same", required=True)
    ap.add_argument("--out", required=True)
    args = ap.parse_args()

    matched_path = Path(args.matched)
    same_path = Path(args.same)
    out_path = Path(args.out)

    m_header, m_rows = read_tsv(matched_path)
    if m_header != ["book_id", "db_book_title", "cover_new_rel_path", "best_excel_title", "excel_count", "match_score"]:
        raise SystemExit(f"Unexpected matched header: {m_header}")

    s_header, s_rows = read_tsv(same_path)
    # We only need the first 6 columns: book_id, db_book_title, cover_new_rel_path, best_excel_title, excel_count, orig_match_score
    if len(s_header) < 6 or s_header[0:5] != ["book_id", "db_book_title", "cover_new_rel_path", "best_excel_title", "excel_count"]:
        raise SystemExit(f"Unexpected same header: {s_header}")

    existing_ids = {r[0] for r in m_rows if r}

    added = 0
    merged_rows = list(m_rows)
    for r in s_rows:
        if len(r) < 6:
            continue
        book_id = r[0]
        if book_id in existing_ids:
            continue
        merged_rows.append([r[0], r[1], r[2], r[3], r[4], r[5]])  # keep orig_match_score as match_score value
        existing_ids.add(book_id)
        added += 1

    out_path.parent.mkdir(parents=True, exist_ok=True)
    with out_path.open("w", encoding="utf-8") as f:
        f.write("\t".join(m_header) + "\n")
        for r in merged_rows:
            # ensure 6 columns
            r6 = (r + [""] * 6)[:6]
            f.write("\t".join(tsv_escape(x) for x in r6) + "\n")

    print(f"Original matched rows: {len(m_rows)}")
    print(f"Added from same-but-lt: {added}")
    print(f"Total merged rows: {len(merged_rows)}")
    print(f"Wrote: {out_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

