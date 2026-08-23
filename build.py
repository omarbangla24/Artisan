#!/usr/bin/env python3
"""
Build the static ARTISAN Chartered Accountants site.

Each file in src/pages/*.html is a content fragment with a small header block:

    <!--
    title: Page title
    description: Meta description
    nav: about          (which main-nav item to highlight; optional)
    output: about.html  (optional, defaults to the fragment filename)
    -->

The fragment is injected into src/layout.html and written to the project root.
Run:  python3 build.py
"""
import re
import pathlib

ROOT = pathlib.Path(__file__).parent
LAYOUT = (ROOT / "src" / "layout.html").read_text(encoding="utf-8")
PAGES = sorted((ROOT / "src" / "pages").glob("*.html"))

HEADER_RE = re.compile(r"\A\s*<!--(.*?)-->", re.S)


def parse(text):
    meta = {}
    m = HEADER_RE.match(text)
    if m:
        for line in m.group(1).strip().splitlines():
            if ":" in line:
                k, v = line.split(":", 1)
                meta[k.strip().lower()] = v.strip()
        text = text[m.end():]
    return meta, text.strip()


def asset_version(rel_path):
    """Cache-busting token from the asset's mtime.

    Without this, browsers (Safari especially) keep serving a stale style.css
    or main.js after a rebuild, and the site looks unchanged until a manual
    hard refresh."""
    f = ROOT / rel_path
    return "?v=%d" % int(f.stat().st_mtime) if f.exists() else ""


def build():
    built = []
    css_v = asset_version("assets/css/style.css")
    js_v = asset_version("assets/js/main.js")
    for page in PAGES:
        meta, content = parse(page.read_text(encoding="utf-8"))
        out_name = meta.get("output", page.name)
        html = (LAYOUT
                .replace("{{CONTENT}}", content)
                .replace("{{TITLE}}", meta.get("title", "ARTISAN Chartered Accountants"))
                .replace("{{DESCRIPTION}}", meta.get("description", ""))
                .replace("{{CSSV}}", css_v)
                .replace("{{JSV}}", js_v)
                .replace("{{SLUG}}", "" if out_name == "index.html" else out_name))

        nav = meta.get("nav")
        if nav:
            html = html.replace('data-nav="%s"' % nav, 'data-nav="%s" class="active"' % nav)

        (ROOT / out_name).write_text(html, encoding="utf-8")
        built.append(out_name)
    return built


if __name__ == "__main__":
    for name in build():
        print("built", name)
