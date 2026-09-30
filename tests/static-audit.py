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
    "functions.php": ["is_page(array('library', 'videos', 'video', 'gallery-page', 'download'))", "is_page('podcast')", "archive-post.css', array('ee-shell'), '1.7.0'"],
    "assets/css/panel.css": ["@media (max-width: 359.98px)", ".ee-panel-pager"],
    "panel/payments.php": ["LIMIT %d OFFSET %d", "evented_panel_pagination"],
    "panel/my-courses.php": ["$courses_per_page", "'no_found_rows'"],
    "inc/performance.php": ["Vazirmatn-Variable.woff2", "print_emoji_detection_script"],
    "template-parts/ee-header.php": ["ee_render_desktop_children", "ee-sub-flyout", "ee-sub-heading", "ee-dn-sub-heading"],
    "assets/css/newhome/ee-shell.css": [".ee-sub-item:hover > .ee-sub-flyout", "right: calc(100% + .4rem)"],
    "assets/js/newhome/evented-home.js": ["ArrowLeft", "aria-expanded"],
    "inc/content_types.php": ["register_post_type($post_type", "'lib' => array(", "'clip' => array(", "'gallery' => array(", "register_taxonomy('galery_cat'", "evented_detach_clip_taxonomies", "unregister_taxonomy_for_object_type($taxonomy, 'clip')"],
    "inc/navigation.php": ["'gallery' === $key", "'is_heading'", "array('video', 'downloads')", "array('clip', 'wpdmpro')", "'sr_playlist'", "EVENTED_NAV_CACHE_VER', '13"],
    "template-parts/ee-resource-single.php": ["ee-resource-single", "the_content()", "comments_template()", "wp_get_original_image_url", "ee-gallery-download", "ee-resource-data", "ee-resource-video-player", "ee-resource-playlist", "evented_clip_playlist", "'clip' !== $ee_type"],
    "inc/template_helpers.php": ["evented_resource_public_meta", "evented_resource_meta_urls", "evented_resource_meta_value_html", "evented_clip_playlist", "_elementor_data", "EVENTED_VIDEO_PLAYLIST_META"],
    "assets/js/resource-video.js": ["data-ee-video-src", "aria-current", "video.load()"],
    "assets/js/admin/video-playlist.js": ["data-ee-video-add", "wp.media", "reindex"],
    "assets/css/admin/video-playlist.css": [".ee-video-admin-row", ".ee-video-admin-fields"],
    "assets/js/admin/download-files.js": ["data-ee-download-add", "wp.media", "reindex"],
    "assets/css/admin/download-files.css": [".ee-download-admin-row", ".ee-download-admin-fields"],
    "inc/downloads.php": ["EVENTED_DOWNLOAD_FILES_META", "register_post_meta('wpdmpro'", "register_post_type('wpdmpro'", "register_taxonomy('wpdmcategory'", "'taxonomies' => array()", "evented_detach_download_taxonomies", "unregister_taxonomy_for_object_type($taxonomy, 'wpdmpro')", "evented_remove_download_taxonomy_menus", "evented_sync_download_files", "__wpdm_files", "evented_download_files", "evented_serve_public_download"],
    "inc/podcasts.php": ["register_post_type('sr_playlist'", "register_taxonomy('playlist-category'", "alb_tracklist", "evented_podcast_tracks", "evented_podcast_media_url"],
    "single-sr_playlist.php": ["evented_podcast_tracks", "data-ee-podcast-player", "data-ee-podcast-src"],
    "assets/js/podcast-player.js": ["data-ee-podcast-src", "audio.load()", "aria-current"],
    "page-download.php": ["ee-download", "Template Name: دانلودها"],
    "single-wpdmpro.php": ["evented_download_data", "ee-download-files", "evented_share_links"],
    "template-parts/ee-download-archive.php": ["download_search", "download_order", "ee-download-grid", "evented_download_data"],
    "inc/video_playlist.php": ["EVENTED_VIDEO_PLAYLIST_META", "register_post_meta('clip'", "evented_save_video_playlist", "evented_migrate_video_playlists", "__block_editor_compatible_meta_box"],
    "single-clip.php": ["'show_meta' => true", "'taxonomy' => ''"],
    "archive-clip.php": ["'ee_taxonomy' => ''"],
    "archive-lib.php": ["ee_taxonomy' => 'wpdmcategory'"],
    "archive-gallery.php": ["ee_taxonomy' => 'galery_cat'", "'ee_category_cards' => true", "'ee_hide_sidebar' => true"],
    "taxonomy-galery_cat.php": ["'ee_hide_sidebar' => true"],
    "single-gallery.php": ["'hide_sidebar' => true"],
    "template-parts/ee-resource-page.php": ["resource_search", "resource_cat", "evented_pagination", "evented_empty_state", "wp_style_is('archive-post'", "'1.7.0'"],
    "page-library.php": ["'post_type' => 'lib'", "'taxonomy' => 'wpdmcategory'"],
    "page-videos.php": ["'post_type' => 'clip'", "'taxonomy' => ''"],
    "page-gallery.php": ["'post_type' => 'gallery'", "'taxonomy' => 'galery_cat'"],
    "page-podcast.php": ["Template Name: پادکست‌ها", "evented_podcast_post_type_candidates", "'intro'      => ''", "'modifier'   => 'podcast'"],
}
for rel, snippets in required_snippets.items():
    text = (ROOT / rel).read_text(encoding="utf-8")
    for snippet in snippets:
        checked += 1
        if snippet not in text:
            errors.append(f"required QA contract missing in {rel}: {snippet}")

forbidden_snippets = {
    "template-parts/ee-download-archive.php": ["download_cat", "wpdmcategory", "ee-resource-chips"],
    "single-wpdmpro.php": ["wpdmcategory", "ee-download-terms"],
}
for rel, snippets in forbidden_snippets.items():
    text = (ROOT / rel).read_text(encoding="utf-8")
    for snippet in snippets:
        checked += 1
        if snippet in text:
            errors.append(f"forbidden QA contract found in {rel}: {snippet}")

if errors:
    for error in errors:
        print(f"FAIL: {error}", file=sys.stderr)
    sys.exit(1)
print(f"Static QA passed ({checked} asset, accessibility and performance contracts).")
