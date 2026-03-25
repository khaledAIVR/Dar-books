from __future__ import annotations

import argparse
import csv
from dataclasses import dataclass
from pathlib import Path
from typing import Any

from flask import Flask, abort, redirect, render_template_string, request, send_from_directory, url_for

from common import now_iso, norm_spaces


def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser()
    p.add_argument("--out-dir", required=True)
    p.add_argument("--mapping", required=True)
    p.add_argument("--host", default="127.0.0.1")
    p.add_argument("--port", type=int, default=5005)
    return p.parse_args()


TEMPLATE = """
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Cover review</title>
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; margin: 16px; }
    .wrap { display: grid; grid-template-columns: 1fr 320px; gap: 16px; align-items: start; }
    img { max-width: 100%; max-height: 85vh; border: 1px solid #ddd; }
    .meta { font-size: 14px; line-height: 1.35; }
    .meta div { margin-bottom: 8px; }
    .btns form { margin-bottom: 8px; }
    button { width: 100%; padding: 10px 12px; font-size: 15px; }
    .ok { background: #1f7a1f; color: #fff; border: none; }
    .del { background: #b42318; color: #fff; border: none; }
    .skip { background: #666; color: #fff; border: none; }
    .small { color: #666; font-size: 12px; margin-top: 12px; }
  </style>
</head>
<body>
  <h2>Cover review</h2>
  {% if item is none %}
    <p>No more downloaded covers to review.</p>
  {% else %}
  <div class="wrap">
    <div>
      <img src="{{ url_for('image_file', filename=item.saved_filename) }}" />
    </div>
    <div class="meta">
      <div><b>Row</b>: {{ item.row }}</div>
      <div><b>Title</b>: {{ item.title }}</div>
      <div><b>Author</b>: {{ item.author }}</div>
      <div><b>Publisher</b>: {{ item.publisher }}</div>
      <div><b>ISBN</b>: {{ item.isbn }}</div>
      <div><b>Query</b>: {{ item.query }}</div>
      <div><b>Image URL</b>: <a href="{{ item.image_url }}" target="_blank">open</a></div>

      <div class="btns">
        <form method="post" action="{{ url_for('accept', row=item.row) }}">
          <button class="ok" type="submit">Accept (A)</button>
        </form>
        <form method="post" action="{{ url_for('delete', row=item.row) }}">
          <button class="del" type="submit">Delete (D)</button>
        </form>
        <form method="post" action="{{ url_for('skip', row=item.row) }}">
          <button class="skip" type="submit">Skip (S)</button>
        </form>
      </div>

      <div class="small">
        Keyboard: A=accept, D=delete, S=skip
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('keydown', (e) => {
      if (e.key === 'a' || e.key === 'A') document.querySelector('form[action$=\"/accept/{{ item.row }}\"]').submit();
      if (e.key === 'd' || e.key === 'D') document.querySelector('form[action$=\"/delete/{{ item.row }}\"]').submit();
      if (e.key === 's' || e.key === 'S') document.querySelector('form[action$=\"/skip/{{ item.row }}\"]').submit();
    });
  </script>
  {% endif %}
</body>
</html>
"""


@dataclass
class MappingRow:
    row: int
    title: str
    author: str
    publisher: str
    isbn: str
    query: str
    image_url: str
    saved_filename: str
    status: str
    downloaded_at: str
    reviewed_at: str


FIELDS = [
    "row",
    "title",
    "author",
    "publisher",
    "isbn",
    "query",
    "image_url",
    "saved_filename",
    "status",
    "downloaded_at",
    "reviewed_at",
]


def load_mapping(path: Path) -> list[MappingRow]:
    if not path.exists():
        return []
    out: list[MappingRow] = []
    with path.open("r", encoding="utf-8", newline="") as f:
        r = csv.DictReader(f, delimiter="\t")
        for d in r:
            try:
                row_num = int(d.get("row") or 0)
            except ValueError:
                continue
            out.append(
                MappingRow(
                    row=row_num,
                    title=norm_spaces(d.get("title") or ""),
                    author=norm_spaces(d.get("author") or ""),
                    publisher=norm_spaces(d.get("publisher") or ""),
                    isbn=norm_spaces(d.get("isbn") or ""),
                    query=norm_spaces(d.get("query") or ""),
                    image_url=norm_spaces(d.get("image_url") or ""),
                    saved_filename=norm_spaces(d.get("saved_filename") or ""),
                    status=norm_spaces(d.get("status") or ""),
                    downloaded_at=norm_spaces(d.get("downloaded_at") or ""),
                    reviewed_at=norm_spaces(d.get("reviewed_at") or ""),
                )
            )
    return out


def save_mapping(path: Path, rows: list[MappingRow]) -> None:
    tmp = path.with_suffix(path.suffix + ".tmp")
    with tmp.open("w", encoding="utf-8", newline="") as f:
        w = csv.writer(f, delimiter="\t")
        w.writerow(FIELDS)
        for r in rows:
            w.writerow(
                [
                    r.row,
                    r.title,
                    r.author,
                    r.publisher,
                    r.isbn,
                    r.query,
                    r.image_url,
                    r.saved_filename,
                    r.status,
                    r.downloaded_at,
                    r.reviewed_at,
                ]
            )
    tmp.replace(path)


def next_downloaded(rows: list[MappingRow]) -> MappingRow | None:
    for r in rows:
        if r.status == "downloaded" and r.saved_filename:
            return r
    return None


def main() -> int:
    args = parse_args()
    out_dir = Path(args.out_dir)
    mapping = Path(args.mapping)
    if not out_dir.exists():
        raise SystemExit(f"out-dir does not exist: {out_dir}")
    if not mapping.exists():
        raise SystemExit(f"mapping TSV does not exist: {mapping}")

    app = Flask(__name__)

    @app.get("/image/<path:filename>")
    def image_file(filename: str):
        return send_from_directory(out_dir, filename)

    @app.get("/")
    def index():
        return redirect(url_for("next_item"))

    @app.get("/next")
    def next_item():
        rows = load_mapping(mapping)
        item = next_downloaded(rows)
        return render_template_string(TEMPLATE, item=item)

    def update_status(row_num: int, status: str, delete_file: bool) -> Any:
        rows = load_mapping(mapping)
        found = None
        for r in rows:
            if r.row == row_num:
                found = r
                break
        if not found:
            abort(404)
        if found.status != "downloaded":
            return redirect(url_for("next_item"))

        if delete_file and found.saved_filename:
            fp = out_dir / found.saved_filename
            try:
                fp.unlink()
            except FileNotFoundError:
                pass

        found.status = status
        found.reviewed_at = now_iso()
        save_mapping(mapping, rows)
        return redirect(url_for("next_item"))

    @app.post("/accept/<int:row>")
    def accept(row: int):
        return update_status(row, "accepted", delete_file=False)

    @app.post("/delete/<int:row>")
    def delete(row: int):
        return update_status(row, "deleted", delete_file=True)

    @app.post("/skip/<int:row>")
    def skip(row: int):
        # no change
        return redirect(url_for("next_item"))

    print(f"Review app running on http://{args.host}:{args.port}")
    app.run(host=args.host, port=args.port, debug=False)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

