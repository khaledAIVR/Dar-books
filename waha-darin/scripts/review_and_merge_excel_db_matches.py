#!/usr/bin/env python3
"""
Review Excel->DB fuzzy UNmatched (<threshold) rows and extract high-confidence
"actually the same title" matches, then merge them into the matched report.

Inputs are the TSVs produced earlier:
  - _excel_colA_db_fuzzy_matched_ge_97.tsv
  - _excel_colA_db_fuzzy_unmatched_lt_97.tsv

We consider a row "same" if it passes Arabic-friendly normalization and
token-overlap + similarity guards (to avoid generic/short false positives).

Usage (from waha-darin/):
  PYTHONPATH=./.pydeps python3 scripts/review_and_merge_excel_db_matches.py \
    --matched storage/app/public/book-covers-by-title/_excel_colA_db_fuzzy_matched_ge_97.tsv \
    --unmatched storage/app/public/book-covers-by-title/_excel_colA_db_fuzzy_unmatched_lt_97.tsv \
    --out storage/app/public/book-covers-by-title/_excel_colA_db_fuzzy_matched_ge_97_plus_llm.tsv \
    --same-out storage/app/public/book-covers-by-title/_excel_colA_db_same_but_lt_97.tsv \
    --review-out storage/app/public/book-covers-by-title/_excel_colA_db_fuzzy_unmatched_lt_97_review.tsv
"""

from __future__ import annotations

import argparse
import re
import unicodedata
from pathlib import Path

from rapidfuzz import fuzz  # type: ignore


AR_DIACRITICS_RE = re.compile(r"[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED]")
AR_MAP = str.maketrans(
    {
        "أ": "ا",
        "إ": "ا",
        "آ": "ا",
        "ٱ": "ا",
        "ؤ": "و",
        "ئ": "ي",
        "ى": "ي",
        "ة": "ه",
        "ـ": "",
    }
)
NON_WORD_RE = re.compile(r"[^\w\s]+", re.UNICODE)

EXTRA_TOKENS = {
    "ج",
    "جزء",
    "الجزء",
    "مجلد",
    "المجلد",
    "طبعه",
    "الطبعه",
    "الطبعة",
    "طبعة",
    "الثانيه",
    "الثانية",
    "الاول",
    "الأول",
    "الاولى",
    "الأولى",
    "ط",
    "رقم",
    "cd",
    "dvd",
    "المجلدات",
}

STOPWORDS = {
    "و",
    "او",
    "أو",
    "في",
    "فى",
    "من",
    "على",
    "عن",
    "الى",
    "إلى",
    "مع",
    "بين",
    "حتى",
    "كما",
    "ما",
    "بعد",
    "قبل",
    "هذا",
    "هذه",
    "ذلك",
    "تلك",
    "هو",
    "هي",
    "هم",
    "هن",
    "ثم",
    "كان",
    "كانت",
    "لم",
    "لن",
    "لا",
    "كل",
    "اي",
    "أي",
}

# If one side is too generic/short, do not accept (unless near-identical).
GENERIC_TITLES = {
    "الف",
    "الله",
    "انا",
    "علي",
    "حياتي",
    "احلام",
    "السر",
    "النبي",
    "الشمس",
    "ورد",
    "هما",
    "حره",
    "حرية",
    "القصر",
    "كيم",
    "حاكم",
    "تاسو",
    "نون",
}


def tsv_unescape(s: str) -> str:
    return s.replace("\\t", "\t").replace("\\r", "\r").replace("\\n", "\n")


def tsv_escape(s: str) -> str:
    return str(s).replace("\t", "\\t").replace("\r", "\\r").replace("\n", "\\n")


def norm(s: str) -> str:
    s = tsv_unescape(str(s))
    s = unicodedata.normalize("NFKC", s)
    s = s.replace("\u00A0", " ")
    s = s.translate(AR_MAP)
    s = AR_DIACRITICS_RE.sub("", s)
    s = s.strip()
    s = NON_WORD_RE.sub(" ", s)
    s = re.sub(r"\s+", " ", s, flags=re.UNICODE).strip()
    return s.casefold()


def strip_ar_prefixes(tok: str) -> str:
    """
    Split common Arabic clitics that are attached to words.
    Examples: ونار -> نار, والادب -> ادب, للناس -> ناس
    """
    t = tok
    while len(t) >= 3 and t[0] in {"و", "ف", "ب", "ك", "ل"}:
        t = t[1:]
    if t.startswith("ال") and len(t) > 2:
        t = t[2:]
    return t


def clean_tokens(s: str) -> list[str]:
    toks = [t for t in norm(s).split(" ") if t]
    out: list[str] = []
    for t in toks:
        if t in EXTRA_TOKENS or t in STOPWORDS:
            continue
        t2 = strip_ar_prefixes(t)
        if not t2:
            continue
        if t2 in EXTRA_TOKENS or t2 in STOPWORDS:
            continue
        out.append(t2)
    return out


def jaccard(a: list[str], b: list[str]) -> float:
    sa, sb = set(a), set(b)
    if not sa and not sb:
        return 1.0
    if not sa or not sb:
        return 0.0
    return len(sa & sb) / len(sa | sb)


def classify_same(excel_title: str, db_title: str) -> tuple[bool, str]:
    a = norm(excel_title)
    b = norm(db_title)
    if not a or not b:
        return False, "empty"

    if a == b:
        return True, "normalized_equal"

    # If either side is generic/very short, only accept if almost identical.
    if a in GENERIC_TITLES or b in GENERIC_TITLES or len(a) <= 4 or len(b) <= 4:
        r = fuzz.ratio(a, b)
        if r >= 99 and abs(len(a) - len(b)) <= 3:
            return True, "generic_near_identical"
        return False, "generic_or_too_short"

    ta = clean_tokens(excel_title)
    tb = clean_tokens(db_title)
    jac = jaccard(ta, tb)

    ratio = float(fuzz.ratio(a, b))
    tok_set = float(fuzz.token_set_ratio(a, b))
    tok_sort = float(fuzz.token_sort_ratio(a, b))

    # Strong: high overlap + high similarity.
    if min(len(set(ta)), len(set(tb))) >= 2:
        if jac >= 0.85 and (ratio >= 93 or tok_set >= 96):
            return True, f"high_overlap jac={jac:.2f} ratio={int(ratio)} tok_set={int(tok_set)}"
        return False, f"not_enough_overlap jac={jac:.2f} ratio={int(ratio)} tok_set={int(tok_set)}"

    # Short-token cases: be strict.
    if tok_set >= 98 and ratio >= 96 and abs(len(a) - len(b)) <= 4:
        return True, f"strict_short ratio={int(ratio)} tok_set={int(tok_set)} tok_sort={int(tok_sort)}"

    return False, f"no_confidence ratio={int(ratio)} tok_set={int(tok_set)} tok_sort={int(tok_sort)} jac={jac:.2f}"


def read_tsv(path: Path) -> tuple[list[str], list[list[str]]]:
    lines = path.read_text(encoding="utf-8", errors="replace").splitlines()
    lines = [ln for ln in lines if ln.strip() != ""]
    if not lines:
        raise SystemExit(f"Empty TSV: {path}")
    header = lines[0].split("\t")
    rows: list[list[str]] = []
    for ln in lines[1:]:
        rows.append([tsv_unescape(x) for x in ln.split("\t")])
    return header, rows


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--matched", required=True)
    ap.add_argument("--unmatched", required=True)
    ap.add_argument("--out", required=True)
    ap.add_argument("--same-out", required=True)
    ap.add_argument("--review-out", required=True)
    args = ap.parse_args()

    matched_path = Path(args.matched)
    unmatched_path = Path(args.unmatched)
    out_path = Path(args.out)
    same_out = Path(args.same_out)
    review_out = Path(args.review_out)

    m_header, m_rows = read_tsv(matched_path)
    expected_m = ["excel_title", "excel_count", "best_db_title", "match_score"]
    if m_header != expected_m:
        raise SystemExit(f"Unexpected matched header: {m_header}")

    u_header, u_rows = read_tsv(unmatched_path)
    expected_u = ["excel_title", "excel_count", "best_db_title", "match_score"]
    if u_header != expected_u:
        raise SystemExit(f"Unexpected unmatched header: {u_header}")

    existing_titles = {r[0] for r in m_rows if r}

    review_cols = [
        "excel_title",
        "excel_count",
        "best_db_title",
        "orig_match_score",
        "llm_same",
        "reason",
        "ratio",
        "token_set_ratio",
        "token_sort_ratio",
        "partial_ratio",
    ]

    review_rows: list[dict[str, str]] = []
    same_rows: list[list[str]] = []

    for r in u_rows:
        if len(r) < 4:
            continue
        excel_title, excel_count, best_db_title, match_score = r[0], r[1], r[2], r[3]
        same, reason = classify_same(excel_title, best_db_title)

        a = norm(excel_title)
        b = norm(best_db_title)
        ratio = int(fuzz.ratio(a, b)) if a and b else 0
        tok_set = int(fuzz.token_set_ratio(a, b)) if a and b else 0
        tok_sort = int(fuzz.token_sort_ratio(a, b)) if a and b else 0
        part = int(fuzz.partial_ratio(a, b)) if a and b else 0

        review_rows.append(
            {
                "excel_title": excel_title,
                "excel_count": str(excel_count),
                "best_db_title": best_db_title,
                "orig_match_score": str(match_score),
                "llm_same": "YES" if same else "NO",
                "reason": reason,
                "ratio": str(ratio),
                "token_set_ratio": str(tok_set),
                "token_sort_ratio": str(tok_sort),
                "partial_ratio": str(part),
            }
        )

        if same:
            # keep same 4-column format as matched TSV
            same_rows.append([excel_title, str(excel_count), best_db_title, str(match_score)])

    # Write review TSV
    review_out.parent.mkdir(parents=True, exist_ok=True)
    with review_out.open("w", encoding="utf-8") as f:
        f.write("\t".join(review_cols) + "\n")
        for rr in review_rows:
            f.write("\t".join(tsv_escape(rr[c]) for c in review_cols) + "\n")

    # Write same-only TSV
    same_out.parent.mkdir(parents=True, exist_ok=True)
    with same_out.open("w", encoding="utf-8") as f:
        f.write("\t".join(expected_u) + "\n")
        for row in same_rows:
            f.write("\t".join(tsv_escape(x) for x in row) + "\n")

    # Merge into matched
    merged = list(m_rows)
    added = 0
    for row in same_rows:
        title = row[0]
        if title in existing_titles:
            continue
        merged.append(row)
        existing_titles.add(title)
        added += 1

    out_path.parent.mkdir(parents=True, exist_ok=True)
    with out_path.open("w", encoding="utf-8") as f:
        f.write("\t".join(expected_m) + "\n")
        for row in merged:
            row4 = (row + [""] * 4)[:4]
            f.write("\t".join(tsv_escape(x) for x in row4) + "\n")

    print(f"Unmatched rows reviewed: {len(u_rows)}")
    print(f"LLM-same extracted: {len(same_rows)}")
    print(f"Original matched rows: {len(m_rows)}")
    print(f"Added to matched: {added}")
    print(f"Merged total rows: {len(merged)}")
    print(f"Wrote review: {review_out}")
    print(f"Wrote same-only: {same_out}")
    print(f"Wrote merged matched: {out_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

