#!/usr/bin/env python3
"""Fast repository QA checks that do not require a WordPress database."""
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
errors = []
checked = 0

# Local assets referenced through PATH_DIR_URL/get_template_directory_uri must exist.
asset_re = re.compile(r"(?:PATH_DIR_URL|get_template_directory_uri\(\))\s*\.\s*['\"](/assets/[^'\"?#]+)")
for path in list(ROOT.rglob("*.php")) + list(ROOT.rglob("*.js")):
    if any(part in {".git", "vendor", "node_modules"} for part in path.parts):
        continue
    text = path.read_text(encoding="utf-8")
    for match in asset_re.finditer(text):
        checked += 1
        reference = match.group(1)
        # A trailing slash is a dynamic directory prefix, not a complete asset path.
        if reference.endswith("/"):
            continue
        target = ROOT / reference.lstrip("/")
        if not target.is_file():
            errors.append(f"missing local asset: {path.relative_to(ROOT)} -> {reference}")

# Every static panel image must have alt text and lazy loading (panel has no above-fold LCP image).
# PHP close tags contain `>`; panel image elements are intentionally kept on one source line.
for path in (ROOT / "panel").glob("*.php"):
    for line in path.read_text(encoding="utf-8").splitlines():
        if "<img" not in line.lower():
            continue
        checked += 1
        if not re.search(r"\balt\s*=", line, flags=re.I):
            errors.append(f"panel image missing alt: {path.relative_to(ROOT)}")
        if not re.search(r"\bloading\s*=\s*['\"]lazy['\"]", line, flags=re.I):
            errors.append(f"panel image missing lazy loading: {path.relative_to(ROOT)}")

required_snippets = {
    "assets/assets_functions.php": ["'profile' === $panel_section", "jalalidatepicker-js"],
    "assets/css/panel.css": ["@media (max-width: 359.98px)", ".ee-panel-pager"],
    "panel/payments.php": ["LIMIT %d OFFSET %d", "evented_panel_pagination"],
    "panel/my-courses.php": ["$courses_per_page", "'no_found_rows'"],
    "inc/performance.php": ["Vazirmatn-Variable.woff2", "print_emoji_detection_script"],
    "template-parts/ee-header.php": ["ee_render_desktop_children", "ee-sub-flyout"],
    "assets/css/newhome/ee-shell.css": [".ee-sub-item:hover > .ee-sub-flyout", "right: calc(100% + .4rem)"],
    "assets/js/newhome/evented-home.js": ["ArrowLeft", "aria-expanded"],
    "inc/content_types.php": ["register_post_type($post_type", "'lib' => array(", "'clip' => array(", "'gallery' => array(", "register_taxonomy('galery_cat'"],
    "template-parts/ee-resource-single.php": ["ee-resource-single", "the_content()", "comments_template()", "wp_get_original_image_url", "ee-gallery-download", "ee-resource-data", "ee-resource-video-player"],
    "inc/template_helpers.php": ["evented_resource_public_meta", "evented_resource_meta_urls", "evented_resource_meta_value_html"],
    "single-clip.php": ["'show_meta' => true"],
    "archive-lib.php": ["ee_taxonomy' => 'wpdmcategory'"],
    "archive-gallery.php": ["ee_taxonomy' => 'galery_cat'", "'ee_category_cards' => true", "'ee_hide_sidebar' => true"],
    "template-parts/ee-resource-page.php": ["resource_search", "resource_cat", "evented_pagination", "evented_empty_state"],
    "page-library.php": ["'post_type' => 'lib'", "'taxonomy' => 'wpdmcategory'"],
    "page-videos.php": ["'post_type' => 'clip'", "'taxonomy' => 'wpdmcategory'"],
    "page-gallery.php": ["'post_type' => 'gallery'", "'taxonomy' => 'galery_cat'"],
}
for rel, snippets in required_snippets.items():
    text = (ROOT / rel).read_text(encoding="utf-8")
    for snippet in snippets:
        checked += 1
        if snippet not in text:
            errors.append(f"required QA contract missing in {rel}: {snippet}")

if errors:
    for error in errors:
        print(f"FAIL: {error}", file=sys.stderr)
    sys.exit(1)
print(f"Static QA passed ({checked} asset, accessibility and performance contracts).")
