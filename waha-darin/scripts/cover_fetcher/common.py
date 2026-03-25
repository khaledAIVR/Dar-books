from __future__ import annotations

import re
import unicodedata
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path


def now_iso() -> str:
    return datetime.now(timezone.utc).replace(microsecond=0).isoformat()


def norm_spaces(s: str) -> str:
    s = str(s or "")
    s = s.replace("\u00A0", " ")
    s = unicodedata.normalize("NFKC", s)
    s = re.sub(r"\s+", " ", s, flags=re.UNICODE).strip()
    return s


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
        "،": " ",
        "؛": " ",
    }
)

AR_DIACRITICS_RE = re.compile(r"[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED\u0640]+")
NON_WORD_RE = re.compile(r"[^\w\s]+", flags=re.UNICODE)


def norm_title_key(s: str) -> str:
    """
    Aggressive Arabic-friendly normalization (similar to earlier matching).
    """
    s = norm_spaces(s)
    if not s:
        return ""
    s = s.translate(AR_MAP)
    s = AR_DIACRITICS_RE.sub("", s)
    s = NON_WORD_RE.sub(" ", s)
    s = re.sub(r"\s+", " ", s, flags=re.UNICODE).strip()
    return s.casefold()


FILENAME_SAFE_RE = re.compile(r"[^\w\s\-\.\(\)]+", flags=re.UNICODE)


def safe_slug(s: str, max_len: int = 80) -> str:
    """
    Filename-safe slug that preserves unicode letters/numbers.
    """
    s = norm_spaces(s)
    if not s:
        return "untitled"
    s = s.replace("/", " ").replace("\\", " ")
    s = FILENAME_SAFE_RE.sub(" ", s)
    s = re.sub(r"\s+", "-", s, flags=re.UNICODE).strip("-")
    if not s:
        s = "untitled"
    if len(s) > max_len:
        s = s[:max_len].rstrip("-")
    return s


def ensure_dir(p: Path) -> None:
    p.mkdir(parents=True, exist_ok=True)


@dataclass(frozen=True)
class BookRow:
    row: int
    title: str
    author: str
    publisher: str
    isbn: str

