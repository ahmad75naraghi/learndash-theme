#!/usr/bin/env python3
"""Fast repository QA checks that do not require a WordPress database."""
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
errors = []
checked = 0

# Certificate PDFs require actual TTF files; mPDF cannot use the site's WOFF2 font.
for rel in ("assets/fonts/Vazirmatn-Regular.ttf", "assets/fonts/Vazirmatn-Bold.ttf"):
    path = ROOT / rel
    checked += 1
    if not path.is_file() or path.stat().st_size < 10000:
        errors.append(f"missing or truncated certificate font: {rel}")
    elif path.read_bytes()[:4] not in (b"\x00\x01\x00\x00", b"OTTO"):
        errors.append(f"invalid TTF/OTF signature: {rel}")

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
    "functions.php": ["is_page(array('library', 'videos', 'video', 'gallery-page', 'download'))", "is_page('podcast')", "archive-post.css', array('ee-shell'), '1.9.0'", "evented-home.css', array('ee-shell'), '1.5.0'", "ee-lms.css', array('ee-shell'), '1.6.0'"],
    "assets/css/panel.css": ["@media (max-width: 359.98px)", ".ee-panel-pager", "Final containment layer", ".transactions-table", "overflow-x: auto"],
    "assets/css/login.css": ["width: min(960px, 100%)", "min-height: 100dvh", "@media (max-width: 420px)", ".otp-field"],
    "page-login.php": ["login.css?ver=1.1.0"],
    "assets/css/newhome/ee-courses.css": ["Narrow viewport hardening", "@media (max-width: 520px)", ".ee-cf-field", ".ee-page-body table"],
    "assets/css/newhome/ee-lms.css": ["Keep LearnDash", ".ee-quiz-body table", ".ee-audio-row audio", "white-space: normal", "فرم اطلاعات شرکت‌کننده", ".wpProQuiz_text > .wpProQuiz_forms", "> div:not(.wpProQuiz_forms)", ".wpProQuiz_forms fieldset:focus-within", "grid-template-columns: repeat(2, minmax(0, 1fr))", ".wpProQuiz_required::after", ".wpProQuiz_formFields > label", ".wpProQuiz_forms > table", "table-layout: fixed", ".learndash-quiz-review__button", "font-size: 16px"],
    "assets/css/single-post.css": ["Long editorial content", ".ee-post-body table", ".ee-comment-list .children", ".ee-podcast-player audio"],
    "assets/css/archive-post.css": ["Archive and resource cards", ".ee-arch-search", "overflow-wrap: break-word"],
    "panel/payments.php": ["LIMIT %d OFFSET %d", "evented_panel_pagination"],
    "panel/my-courses.php": ["$courses_per_page", "'no_found_rows'"],
    "inc/performance.php": ["Vazirmatn-Variable.woff2", "print_emoji_detection_script"],
    "inc/live_search.php": ["'clip'         => 'smart_display'", "'sr_playlist'  => 'podcasts'", "'lib'          => 'local_library'", "'wpdmpro'      => 'download'", "add_query_arg($more_args"],
    "inc/theme_options.php": ["current_user_can('unfiltered_html')", "current_user_can('manage_options')", "array('response' => 403)", "!get_userdata($target_user_id)"],
    "inc/theme_updater.php": ["'evented-edu.zip' === $name", "source zipball omits generated production dependencies"],
    "inc/pwa.php": ["X-Evented-Private: 1", "EVENTED_PWA_VER = '1.0.1'"],
    "assets/js/newhome/ee-sw.js": ["res.headers.get('cache-control')", "res.headers.get('vary')", "x-evented-private", "privateResponse"],
    "template-parts/ee-header.php": ["ee_render_desktop_children", "ee-sub-flyout", "ee-sub-heading", "ee-dn-sub-heading"],
    "assets/css/newhome/ee-shell.css": [".ee-sub-item:hover > .ee-sub-flyout", "right: calc(100% + .4rem)", "overflow-wrap: break-word", "@media (max-width: 639.98px)"],
    "assets/css/newhome/evented-home.css": ["تایپوگرافی و چیدمان موبایل صفحهٔ اصلی", ".ee-sec-title", "white-space: nowrap", "@media (max-width: 359.98px)", ".ee-home button.ee-slide-dot", "min-height: .38rem", "--ee-slider-height-mobile"],
    "assets/css/admin/theme-settings.css": [".evented-slider-size", ".evented-number-unit"],
    "inc/theme_settings.php": ["EVENTED_OPT_SLIDER_HEIGHT", "EVENTED_OPT_SLIDER_HEIGHT_MOBILE", "evented_get_home_slider_height", "name=\"slider_height\"", "name=\"slider_height_mobile\"", "max(220, min(720", "max(180, min(520"],
    "front-page.php": ["--ee-slider-height:", "--ee-slider-height-mobile:"],
    "assets/css/newhome/ee-catalog.css": ["سربرگ و کارت‌های کاتالوگ روی موبایل باریک", ".ee-ct-title", "text-overflow: ellipsis"],
    "assets/js/newhome/evented-home.js": ["ArrowLeft", "aria-expanded"],
    "tests/visual-audit.mjs": ["guestLogin", "playground_auto_login_already_happened", "course-single", "lesson-single", "quiz-single", "podcast-single", "escapedContent", "formLayout.overlapping", "quiz form control(s) escape the form"],
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
    "inc/reports.php": ["evented_reports_admin_menu", "گزارش‌گیری", "evented_reports_course_rows", "evented_reports_quiz_rows", "evented_reports_export", "evented_reports_capture_quiz_ip", "evented_reports_card_number_shortcode", "update_meta_cache('user'", "admin_post_evented_export_report", "check_admin_referer('evented_export_report')"],
    "inc/certificates.php": ["Vazirmatn-Regular.ttf", "Vazirmatn-Bold.ttf", "'eventedcert'", "'useOTL'", "lang=\"fa\""],
    "assets/css/admin/reports.css": [".ee-report-filters", ".ee-report-table-wrap", "@media print"],
    "assets/js/admin/reports.js": ["data-ee-report-print", "window.print()"],
    "assets/css/card-number.css": [".ee-card-form", ".ee-card-saved"],
    "inc/podcasts.php": ["EVENTED_PODCAST_TRACKS_META", "register_post_meta('sr_playlist'", "register_post_type('sr_playlist'", "register_taxonomy('playlist-category'", "alb_tracklist", "evented_legacy_podcast_tracks", "evented_podcast_tracks", "evented_podcast_media_url", "evented_sync_podcast_tracks", "evented_save_podcast_tracks", "__block_editor_compatible_meta_box"],
    "single-sr_playlist.php": ["evented_podcast_tracks", "data-ee-podcast-player", "data-ee-podcast-src"],
    "assets/js/podcast-player.js": ["data-ee-podcast-src", "audio.load()", "aria-current"],
    "assets/js/admin/podcast-tracks.js": ["data-ee-podcast-add", "wp.media", "reindex"],
    "assets/css/admin/podcast-tracks.css": [".ee-podcast-admin-row", ".ee-podcast-admin-fields"],
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
    "template-parts/ee-resource-page.php": ["resource_search", "resource_cat", "evented_pagination", "evented_empty_state", "wp_style_is('archive-post'", "'1.9.0'"],
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
    "inc/live_search.php": ["rawurlencode($q)", "'video'        => 'smart_display'", "'downloads'    => 'download'"],
    "inc/theme_updater.php": ["$payload['zipball_url']"],
    "assets/js/newhome/ee-sw.js": ["res.headers.get('set-cookie')"],
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
