#!/usr/bin/env bash
# Smoke test یک نصب واقعی وردپرس که قالب روی آن فعال و کاربر مدیر وارد شده است.
# استفاده: bash bin/smoke-test.sh http://127.0.0.1:9400 [/path/to/debug.log]
set -euo pipefail

base="${1:-http://127.0.0.1:9400}"
debug_log="${2:-}"
base="${base%/}"
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
cookie="$tmp/cookies.txt"
failures=0
requests=0

fail() {
	printf 'FAIL: %s\n' "$*" >&2
	if [[ "${GITHUB_ACTIONS:-}" == true ]]; then printf '::error title=WordPress smoke::%s\n' "$*"; fi
	failures=$((failures + 1))
}
fetch() {
	local path="$1" out="$2" code
	code=$(curl -sS --max-time 30 --max-redirs 10 -c "$cookie" -b "$cookie" -L -o "$out" -w '%{http_code}' "$base$path") || {
		fail "request failed: $path"; return 1;
	}
	requests=$((requests + 1))
	[[ "$code" == 200 ]] || fail "$path returned HTTP $code"
	if grep -Eqi 'Fatal error|Parse error|<b>Warning</b>|<b>Deprecated</b>' "$out"; then
		fail "PHP error leaked into response: $path"
	fi
}
require_text() {
	grep -Fq "$2" "$1" || fail "$3"
}
reject_text() {
	if grep -Fq "$2" "$1"; then fail "$3"; fi
}

# در CI ابتدا auto-login خود Playground را کامل می‌کنیم؛ staging می‌تواند credential صریح بدهد.
if [[ "${SMOKE_PLAYGROUND_AUTO_LOGIN:-0}" == 1 ]]; then
	curl -sS --max-time 30 --max-redirs 10 -L -c "$cookie" -b "$cookie" \
		"$base/wp-admin/" -o "$tmp/login.html"
	grep -Fq 'wp-admin-bar-my-account' "$tmp/login.html" || fail 'Playground auto-login failed'
elif [[ -n "${SMOKE_USERNAME:-}" && -n "${SMOKE_PASSWORD:-}" ]]; then
	curl -sS --max-time 30 -c "$cookie" "$base/wp-login.php" -o /dev/null
	curl -sS --max-time 30 --max-redirs 10 -L -c "$cookie" -b "$cookie" \
		--data-urlencode "log=$SMOKE_USERNAME" --data-urlencode "pwd=$SMOKE_PASSWORD" \
		--data-urlencode 'wp-submit=Log In' --data-urlencode "redirect_to=$base/wp-admin/" \
		--data-urlencode 'testcookie=1' "$base/wp-login.php" -o "$tmp/login.html"
	grep -Fq 'wordpress_logged_in_' "$cookie" || fail 'explicit WordPress login failed'
fi

# درخواست اول cookie ورود خودکار Playground را می‌گیرد؛ روی staging باید cookie معتبر فراهم باشد.
fetch '/' "$tmp/home.html"
require_text "$tmp/home.html" 'id="ee-main"' 'home did not render the evented shell'
require_text "$tmp/home.html" ' defer src=' 'theme scripts are not deferred'
require_text "$tmp/home.html" 'Vazirmatn-Variable.woff2' 'main font preload is missing'
require_text "$tmp/home.html" "href=\"$base/gallery/\"" 'gallery menu must link to /gallery/'
if ! python3 - "$tmp/home.html" <<'PY'
from pathlib import Path
import re
import sys
html = Path(sys.argv[1]).read_text(encoding='utf-8')
def plain_item(path):
    item = re.search(r'<div class="([^"]*\bee-nav-item\b[^"]*)"[^>]*>\s*<a href="[^"]*/' + path + r'/"', html)
    return item and 'has-sub' not in item.group(1).split()
raise SystemExit(0 if plain_item('videos') and plain_item('download') else 1)
PY
then
	fail 'video/download navigation items must not have taxonomy submenus'
fi

# CPTهای مهاجرت‌کرده بدون CPT UI باید آرشیو و نمای تکی سالم داشته باشند.
resource_paths=('lib/' 'clip/' 'gallery/' 'podcast/' 'galery_cat/%DA%AF%D8%B2%D8%A7%D8%B1%D8%B4-%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%DB%8C/' 'lib/lib-item-1/' 'clip/clip-item-1/' 'gallery/gallery-item-1/' 'download/download-item-1/' 'podcast-episode/legacy-podcast-fixture/')
for path in "${resource_paths[@]}"; do
	name=${path//\//-}
	fetch "/$path" "$tmp/resource-$name.html"
	require_text "$tmp/resource-$name.html" 'id="ee-main"' "custom content route did not render: /$path"
done
require_text "$tmp/resource-podcast-.html" 'پادکست بازیابی‌شدهٔ آزمایشی' 'legacy Sonaar playlist is missing from /podcast/'
reject_text "$tmp/resource-podcast-.html" 'هنوز پادکستی منتشر نشده است' 'podcast page still renders the empty state despite legacy data'
require_text "$tmp/resource-podcast-episode-legacy-podcast-fixture-.html" 'data-ee-podcast-player' 'legacy podcast player did not render'
require_text "$tmp/resource-podcast-episode-legacy-podcast-fixture-.html" 'توصیهٔ تربیتی اول' 'first serialized Sonaar track was not recovered'
require_text "$tmp/resource-podcast-episode-legacy-podcast-fixture-.html" 'توصیهٔ تربیتی دوم' 'second serialized Sonaar track was not recovered'
require_text "$tmp/resource-podcast-episode-legacy-podcast-fixture-.html" '/wp-content/uploads/podcast-1.mp3' 'legacy Sonaar stream URL was not recovered'
require_text "$tmp/resource-podcast-episode-legacy-podcast-fixture-.html" 'podcast-player.js?ver=1.0.0' 'podcast player behavior was not enqueued'
fetch '/wp-json/wp/v2/types/sr_playlist' "$tmp/podcast-rest-type.json"
require_text "$tmp/podcast-rest-type.json" '"slug":"sr_playlist"' 'legacy Sonaar post type is missing from REST'
fetch '/wp-json/wp/v2/sr_playlist?slug=legacy-podcast-fixture' "$tmp/podcast-rest-item.json"
require_text "$tmp/podcast-rest-item.json" '"_evented_podcast_tracks"' 'canonical podcast tracks meta is missing from REST'
podcast_id=$(python3 - "$tmp/podcast-rest-item.json" <<'PY'
import json, sys
rows=json.load(open(sys.argv[1], encoding='utf-8'))
print(rows[0]['id'] if rows else '')
PY
)
if [[ -n "$podcast_id" ]]; then
	fetch "/wp-admin/post.php?post=$podcast_id&action=edit" "$tmp/podcast-editor.html"
	require_text "$tmp/podcast-editor.html" 'data-ee-podcast-editor' 'podcast tracks editor is missing'
	require_text "$tmp/podcast-editor.html" 'data-ee-podcast-add' 'podcast audio add button is missing'
	require_text "$tmp/podcast-editor.html" 'podcast-tracks.js?ver=1.0.0' 'podcast editor behavior was not enqueued'
	require_text "$tmp/podcast-editor.html" 'توصیهٔ تربیتی دوم' 'legacy Sonaar tracks were not prefilled in the editor'
else
	fail 'podcast fixture ID was not returned by REST'
fi
fetch '/wp-admin/edit.php?post_type=sr_playlist&page=evented-podcast-sync' "$tmp/podcast-sync.html"
require_text "$tmp/podcast-sync.html" 'شروع همگام‌سازی امن' 'podcast synchronization tool is missing'
podcast_sync_nonce=$(python3 - "$tmp/podcast-sync.html" <<'PY'
from html.parser import HTMLParser
import sys
class P(HTMLParser):
    value=''
    def handle_starttag(self, tag, attrs):
        attrs=dict(attrs)
        if tag=='input' and attrs.get('name')=='_wpnonce': self.value=attrs.get('value','')
p=P(); p.feed(open(sys.argv[1], encoding='utf-8').read()); print(p.value)
PY
)
if [[ -n "$podcast_sync_nonce" ]]; then
	code=$(curl -sS --max-time 60 --max-redirs 10 -c "$cookie" -b "$cookie" -L --data-urlencode "_wpnonce=$podcast_sync_nonce" --data-urlencode 'evented_sync_podcasts=1' -o "$tmp/podcast-sync-result.html" -w '%{http_code}' "$base/wp-admin/edit.php?post_type=sr_playlist&page=evented-podcast-sync") || code=000
	requests=$((requests + 1)); [[ "$code" == 200 ]] || fail "podcast synchronization returned HTTP $code"
	if ! grep -Eq 'همگام‌شده: 1|قبلاً استاندارد: 1' "$tmp/podcast-sync-result.html"; then fail 'legacy Sonaar tracks were not synchronized'; fi
	fetch '/wp-json/wp/v2/sr_playlist?slug=legacy-podcast-fixture' "$tmp/podcast-rest-synced.json"
	if ! python3 - "$tmp/podcast-rest-synced.json" <<'PY'
import json, sys
rows=json.load(open(sys.argv[1], encoding='utf-8'))
tracks=rows[0].get('meta',{}).get('_evented_podcast_tracks',[]) if rows else []
raise SystemExit(0 if len(tracks)==2 and tracks[0].get('title')=='توصیهٔ تربیتی اول' and tracks[1].get('audio_url','').endswith('podcast-2.mp3') else 1)
PY
	then fail 'synchronized canonical podcast data is invalid'; fi
	code=$(curl -sS --max-time 60 --max-redirs 10 -c "$cookie" -b "$cookie" -L --data-urlencode "_wpnonce=$podcast_sync_nonce" --data-urlencode 'evented_sync_podcasts=1' -o "$tmp/podcast-sync-second.html" -w '%{http_code}' "$base/wp-admin/edit.php?post_type=sr_playlist&page=evented-podcast-sync") || code=000
	requests=$((requests + 1)); [[ "$code" == 200 ]] || fail "second podcast synchronization returned HTTP $code"
	require_text "$tmp/podcast-sync-second.html" 'قبلاً استاندارد: 1' 'podcast synchronization is not idempotent'
else
	fail 'podcast synchronization nonce is missing'
fi
fetch '/wp-json/wp/v2/types/clip' "$tmp/clip-rest-type.json"
require_text "$tmp/clip-rest-type.json" '"slug":"clip"' 'clip REST type is missing'
reject_text "$tmp/clip-rest-type.json" 'wpdmcategory' 'clip remains associated with the library category taxonomy'
reject_text "$tmp/clip-rest-type.json" 'wpdmtag' 'clip remains associated with the library tag taxonomy'
fetch '/wp-json/wp/v2/types/wpdmpro' "$tmp/download-rest-type.json"
require_text "$tmp/download-rest-type.json" '"slug":"wpdmpro"' 'download REST type is missing'
reject_text "$tmp/download-rest-type.json" 'wpdmcategory' 'downloads remain associated with the library category taxonomy'
reject_text "$tmp/download-rest-type.json" 'wpdmtag' 'downloads remain associated with a tag taxonomy'
fetch '/wp-admin/edit.php?post_type=wpdmpro' "$tmp/download-admin.html"
reject_text "$tmp/download-admin.html" 'taxonomy=wpdmcategory&amp;post_type=wpdmpro' 'download admin menu still exposes library categories'
reject_text "$tmp/download-admin.html" 'taxonomy=wpdmtag&amp;post_type=wpdmpro' 'download admin menu still exposes tags'
fetch '/wp-json/wp/v2/wpdmpro?slug=download-item-1' "$tmp/download-rest-item.json"
require_text "$tmp/download-rest-item.json" '"_evented_download_files"' 'canonical download files meta is missing from Gutenberg REST data'
download_id=$(python3 - "$tmp/download-rest-item.json" <<'PY'
import json, sys
rows = json.load(open(sys.argv[1], encoding='utf-8'))
print(rows[0]['id'] if rows else '')
PY
)
if [[ -n "$download_id" ]]; then
	fetch "/wp-admin/post.php?post=$download_id&action=edit" "$tmp/download-editor.html"
	require_text "$tmp/download-editor.html" 'data-ee-download-editor' 'Gutenberg download files editor is missing'
	require_text "$tmp/download-editor.html" 'data-ee-download-add' 'download file add button is missing'
	require_text "$tmp/download-editor.html" 'download-files.js?ver=1.0.0' 'download editor behavior was not enqueued'
	require_text "$tmp/download-editor.html" 'راهنمای آزمایشی 1' 'legacy WPDM file was not prefilled in the editor'
	reject_text "$tmp/download-editor.html" 'wpdmcategorydiv' 'download editor still renders the library category box'
	reject_text "$tmp/download-editor.html" 'tagsdiv-wpdmtag' 'download editor still renders a tag box'
else
	fail 'download fixture ID was not returned by REST'
fi
fetch '/wp-admin/edit.php?post_type=wpdmpro&page=evented-download-sync' "$tmp/download-sync.html"
require_text "$tmp/download-sync.html" 'شروع همگام‌سازی امن' 'download data synchronization tool is missing'
download_sync_nonce=$(python3 - "$tmp/download-sync.html" <<'PY'
from html.parser import HTMLParser
import sys
class P(HTMLParser):
    value = ''
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input' and attrs.get('name') == '_wpnonce': self.value = attrs.get('value', '')
p=P(); p.feed(open(sys.argv[1], encoding='utf-8').read()); print(p.value)
PY
)
if [[ -n "$download_sync_nonce" ]]; then
	code=$(curl -sS --max-time 60 --max-redirs 10 -c "$cookie" -b "$cookie" -L \
		--data-urlencode "_wpnonce=$download_sync_nonce" --data-urlencode 'evented_sync_downloads=1' \
		-o "$tmp/download-sync-result.html" -w '%{http_code}' "$base/wp-admin/edit.php?post_type=wpdmpro&page=evented-download-sync") || code=000
	requests=$((requests + 1)); [[ "$code" == 200 ]] || fail "download synchronization returned HTTP $code"
	if ! grep -Eq 'همگام‌شده: 13|قبلاً استاندارد: 13' "$tmp/download-sync-result.html"; then fail 'legacy WPDM files were not synchronized'; fi
	fetch '/wp-json/wp/v2/wpdmpro?slug=download-item-1' "$tmp/download-rest-synced.json"
	if ! python3 - "$tmp/download-rest-synced.json" <<'PY'
import json, sys
rows=json.load(open(sys.argv[1], encoding='utf-8'))
files=rows[0].get('meta', {}).get('_evented_download_files', []) if rows else []
raise SystemExit(0 if files and files[0].get('title') == 'راهنمای آزمایشی 1' and files[0].get('legacy_path') == 'fixture-guide.pdf' else 1)
PY
	then fail 'synchronized canonical download data is invalid'; fi
else
	fail 'download synchronization nonce is missing'
fi
fetch '/wp-json/wp/v2/clip?slug=clip-item-1' "$tmp/clip-rest-item.json"
require_text "$tmp/clip-rest-item.json" '"_evented_video_playlist"' 'canonical video playlist meta is missing from Gutenberg REST data'
clip_id=$(python3 - "$tmp/clip-rest-item.json" <<'PY'
import json, sys
rows = json.load(open(sys.argv[1], encoding='utf-8'))
print(rows[0]['id'] if rows else '')
PY
)
if [[ -n "$clip_id" ]]; then
	fetch "/wp-admin/post.php?post=$clip_id&action=edit" "$tmp/clip-editor.html"
	require_text "$tmp/clip-editor.html" 'data-ee-video-editor' 'Gutenberg video playlist editor is missing'
	require_text "$tmp/clip-editor.html" 'data-ee-video-add' 'video add button is missing from the editor'
	require_text "$tmp/clip-editor.html" 'video-playlist.js?ver=1.0.0' 'video editor behavior was not enqueued'
	require_text "$tmp/clip-editor.html" 'دشمن شناسی عاشورا ۲' 'legacy Elementor items were not prefilled in the editor'
else
	fail 'clip fixture ID was not returned by REST'
fi
fetch '/wp-admin/edit.php?post_type=clip&page=evented-video-migration' "$tmp/video-migration.html"
require_text "$tmp/video-migration.html" 'شروع یکپارچه‌سازی امن' 'video migration tool is missing'
migration_nonce=$(python3 - "$tmp/video-migration.html" <<'PY'
from html.parser import HTMLParser
import sys
class NonceParser(HTMLParser):
    value = ''
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input' and attrs.get('name') == '_wpnonce': self.value = attrs.get('value', '')
p = NonceParser(); p.feed(open(sys.argv[1], encoding='utf-8').read()); print(p.value)
PY
)
if [[ -n "$migration_nonce" ]]; then
	code=$(curl -sS --max-time 60 --max-redirs 10 -c "$cookie" -b "$cookie" -L \
		--data-urlencode "_wpnonce=$migration_nonce" --data-urlencode 'evented_migrate_video_playlists=1' \
		-o "$tmp/video-migration-result.html" -w '%{http_code}' "$base/wp-admin/edit.php?post_type=clip&page=evented-video-migration") || code=000
	requests=$((requests + 1))
	[[ "$code" == 200 ]] || fail "video migration returned HTTP $code"
	if ! grep -Eq 'منتقل‌شده: 1|قبلاً استاندارد: 1' "$tmp/video-migration-result.html"; then fail 'legacy playlist was not migrated to the canonical meta'; fi
	fetch '/wp-json/wp/v2/clip?slug=clip-item-1' "$tmp/clip-rest-migrated.json"
	if ! python3 - "$tmp/clip-rest-migrated.json" <<'PY'
import json, sys
rows = json.load(open(sys.argv[1], encoding='utf-8'))
playlist = rows[0].get('meta', {}).get('_evented_video_playlist', []) if rows else []
raise SystemExit(0 if any(item.get('title') == 'دشمن شناسی عاشورا ۲' for item in playlist) else 1)
PY
	then fail 'migrated playlist is missing from REST meta'; fi
else
	fail 'video migration nonce is missing'
fi
require_text "$tmp/resource-lib-lib-item-1-.html" 'ee-resource-single' 'library single template did not render'
require_text "$tmp/resource-clip-clip-item-1-.html" 'ee-resource-single' 'video single template did not render'
reject_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-post-content"' 'video single must not render Gutenberg/Elementor content below the custom player'
reject_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-post-hero' 'video thumbnail must be used as the player poster, not rendered as a duplicate hero'
require_text "$tmp/resource-lib-lib-item-1-.html" 'class="ee-post-content"' 'non-video resources unexpectedly lost their content'
reject_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-meta-cat"' 'video single must not render a taxonomy category'
reject_text "$tmp/resource-clip-.html" 'class="ee-arch-chips"' 'video archive inherited library taxonomy filters'
require_text "$tmp/resource-lib-.html" 'منابع کتابخانه' 'library taxonomy fixture did not render'
require_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-resource-data"' 'video metadata section did not render'
reject_text "$tmp/resource-clip-clip-item-1-.html" '<code dir="ltr">_video_url</code>' 'technical video meta should not be shown beside a recovered playlist'
require_text "$tmp/resource-clip-clip-item-1-.html" '/wp-content/uploads/video-1.mp4' 'video URL was not recovered from post meta'
require_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-resource-video-player"' 'direct video URL did not render a player'
require_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-resource-playlist"' 'Elementor video playlist was not recovered'
require_text "$tmp/resource-clip-clip-item-1-.html" 'دشمن شناسی عاشورا ۲' 'Elementor playlist titles are missing'
require_text "$tmp/resource-clip-clip-item-1-.html" 'resource-video.js?ver=1.1.0' 'video playlist behavior was not enqueued'
reject_text "$tmp/resource-clip-clip-item-1-.html" 'clip_playlist' 'raw legacy playlist metadata must not be shown publicly'
reject_text "$tmp/resource-clip-clip-item-1-.html" '_elementor_data' 'raw Elementor JSON must not be publicly rendered'
reject_text "$tmp/resource-clip-clip-item-1-.html" '_edit_lock' 'internal editing metadata must not be publicly rendered'
reject_text "$tmp/resource-clip-clip-item-1-.html" 'must-not-be-public' 'sensitive video metadata leaked publicly'
require_text "$tmp/resource-gallery-gallery-item-1-.html" 'ee-resource-single' 'gallery single template did not render'
require_text "$tmp/resource-gallery-.html" 'ee-gallery-category-grid' 'gallery archive category cards are missing'
require_text "$tmp/resource-gallery-.html" 'class="ee-gallery-category-group-head"' 'gallery parent category heading is missing'
if ! python3 - "$tmp/resource-gallery-.html" "$tmp/home.html" <<'PY'
from pathlib import Path
import re
import sys
archive = Path(sys.argv[1]).read_text(encoding='utf-8').split('ee-gallery-categories', 1)[-1]
menu = Path(sys.argv[2]).read_text(encoding='utf-8')
pos = [archive.find(label) for label in ('گزارش تصویری', 'مراسم و رویدادها', 'فعالیت‌های آموزشی')]
archive_parent_is_plain = not re.search(r'<a class="ee-gallery-category-card"[^>]*>.*?گزارش تصویری.*?</a>', archive, re.S)
menu_parent_is_plain = 'ee-sub-heading' in menu and not re.search(r'<a class="ee-sub-link[^"]*"[^>]*>(?:(?!</a>).)*گزارش تصویری', menu, re.S)
raise SystemExit(0 if min(pos) >= 0 and pos == sorted(pos) and archive_parent_is_plain and menu_parent_is_plain else 1)
PY
then
	fail 'gallery parent must be a non-link heading followed by count-sorted child categories'
fi
gallery_tax_file=$(find "$tmp" -maxdepth 1 -name 'resource-galery_cat-*.html' | head -n 1)
reject_text "$tmp/resource-gallery-.html" 'class="ee-side"' 'gallery archive must not render a sidebar'
reject_text "$gallery_tax_file" 'class="ee-side"' 'gallery taxonomy must not render a sidebar'
reject_text "$tmp/resource-gallery-gallery-item-1-.html" 'class="ee-side"' 'gallery single must not render a sidebar'
require_text "$tmp/resource-gallery-gallery-item-1-.html" 'class="ee-btn ee-btn-primary ee-gallery-download"' 'original gallery image download is missing'
require_text "$tmp/resource-gallery-gallery-item-1-.html" 'target="_blank" rel="noopener"' 'gallery thumbnail does not open the original image in a new page'
reject_text "$tmp/resource-gallery-.html" 'gallery-item-13' 'gallery without a featured image leaked into the archive'
reject_text "$tmp/resource-gallery-.html" 'ee-gallery-category-placeholder' 'gallery category did not inherit a contained gallery image'
gallery_empty_code=$(curl -sS --max-time 30 --max-redirs 10 -c "$cookie" -b "$cookie" -L -o "$tmp/gallery-empty.html" -w '%{http_code}' "$base/gallery/gallery-item-13/") || gallery_empty_code=000
requests=$((requests + 1))
[[ "$gallery_empty_code" == 404 ]] || fail "imageless gallery single returned HTTP $gallery_empty_code instead of 404"

# داده‌های wpdmpro باید بدون افزونه در همان /download/ و صفحهٔ تکی باقی بمانند.
fetch '/download/' "$tmp/download-archive.html"
require_text "$tmp/download-archive.html" 'class="ee-resource-grid ee-download-grid"' 'independent download archive is missing'
require_text "$tmp/download-archive.html" 'دانلود آزمایشی 1' 'WPDM package data is missing from /download/'
reject_text "$tmp/download-archive.html" 'name="download_cat"' 'download archive must not expose a taxonomy filter'
reject_text "$tmp/download-archive.html" 'class="ee-resource-chips"' 'download archive must not expose taxonomy chips'
require_text "$tmp/resource-download-download-item-1-.html" 'class="ee-download-files"' 'WPDM single download files are missing'
reject_text "$tmp/resource-download-download-item-1-.html" 'class="ee-download-terms"' 'download single must not render taxonomy terms'
require_text "$tmp/resource-download-download-item-1-.html" 'راهنمای آزمایشی 1' 'WPDM file metadata was not recovered'
download_url=$(python3 - "$tmp/resource-download-download-item-1-.html" <<'PY2'
from html.parser import HTMLParser
import sys
class P(HTMLParser):
    url = ''
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'a' and 'evented_public_download' in a.get('href', '') and not self.url: self.url = a['href']
p=P(); p.feed(open(sys.argv[1], encoding='utf-8').read()); print(p.url.replace('&amp;', '&'))
PY2
)
if [[ -n "$download_url" ]]; then
	code=$(curl -sS --max-time 30 -c "$cookie" -b "$cookie" -L -o "$tmp/downloaded-file.bin" -w '%{http_code}' "$download_url") || code=000
	requests=$((requests + 1)); [[ "$code" == 200 ]] || fail "public download endpoint returned HTTP $code"
	require_text "$tmp/downloaded-file.bin" 'fixture download file' 'public download endpoint returned the wrong file'
else
	fail 'public download URL is missing'
fi
for social_asset in eitaa.svg bale.svg rubika.svg igap.png soroush.svg; do
	require_text "$tmp/resource-download-download-item-1-.html" "assets/images/social/$social_asset" "share block is missing real social icon: $social_asset"
done

# برگه‌های انتخاب‌پذیر منابع باید query مستقل، کنترل فیلتر و asset مشترک را رندر کنند.
resource_pages=('library/' 'videos/' 'gallery-page/')
for path in "${resource_pages[@]}"; do
	name=${path//\//-}
	fetch "/$path" "$tmp/resource-page-$name.html"
	require_text "$tmp/resource-page-$name.html" 'class="ee-resource-page-main"' "resource page template did not render: /$path"
	require_text "$tmp/resource-page-$name.html" 'name="resource_search"' "resource search is missing: /$path"
	require_text "$tmp/resource-page-$name.html" 'archive-post.css' "resource page stylesheet is missing: /$path"
done
require_text "$tmp/resource-page-library-.html" 'name="resource_cat"' 'library taxonomy filter is missing'
reject_text "$tmp/resource-page-videos-.html" 'name="resource_cat"' 'videos page must not render a taxonomy filter'
require_text "$tmp/resource-page-gallery-page-.html" 'name="resource_cat"' 'gallery taxonomy filter is missing'
reject_text "$tmp/resource-page-gallery-page-.html" 'class="ee-side"' 'gallery page template must not render a sidebar'
require_text "$tmp/resource-page-videos-.html" 'archive-post.css?ver=1.9.0' 'videos page did not enqueue its dedicated layout stylesheet'
require_text "$tmp/resource-page-videos-.html" 'page-template-default' 'videos fixture unexpectedly relies on a manually assigned page template'
video_grid_count=$(grep -Foc 'class="ee-resource-grid"' "$tmp/resource-page-videos-.html" || true)
[[ "$video_grid_count" -eq 1 ]] || fail "videos page rendered its archive $video_grid_count times instead of once"
reject_text "$tmp/resource-page-videos-.html" 'wp-audio-shortcode' 'videos page executed legacy page shortcodes and duplicated structural content'
fetch '/library/page/2/' "$tmp/resource-page-paged.html"
require_text "$tmp/resource-page-paged.html" 'کتاب آزمایشی' 'resource pagination did not return the second page'
fetch '/videos/?resource_search=%D9%88%DB%8C%D8%AF%D8%A6%D9%88' "$tmp/resource-page-search.html"
require_text "$tmp/resource-page-search.html" 'ee-resource-card' 'resource search did not return matching cards'
fetch '/gallery-page/?resource_cat=999999' "$tmp/resource-page-empty.html"
require_text "$tmp/resource-page-empty.html" 'ee-empty-state' 'resource empty state did not render for an unmatched taxonomy filter'

panel_paths=(panel panel/profile panel/my-courses panel/payments panel/wishlist panel/certificates panel/settings)
for path in "${panel_paths[@]}"; do
	name=${path//\//-}
	fetch "/$path/" "$tmp/$name.html"
	require_text "$tmp/$name.html" 'class="ee-panel' "panel shell missing on /$path/ (authenticated session required)"
	require_text "$tmp/$name.html" 'data-ee-panel-nav' "mobile panel navigation missing on /$path/"
	require_text "$tmp/$name.html" 'data-ee-panel-help' "section help button missing on /$path/"
	require_text "$tmp/$name.html" 'class="ee-panel-help-text"' "section help text missing on /$path/"
done

# دارایی 23KB تقویم فقط باید روی پروفایل باشد.
require_text "$tmp/panel-profile.html" 'jalalidatepicker.min.js' 'profile date picker is missing'
for name in panel-payments panel-wishlist panel-certificates panel-settings panel-my-courses; do
	reject_text "$tmp/$name.html" 'jalalidatepicker.min.js' "date picker loaded unnecessarily on $name"
done

# فقط روی Playground یکبار مسیر واقعی AJAX پروفایل و ارقام فارسی آزمایش می‌شود.
if [[ "${SMOKE_WRITE_PROFILE:-0}" == 1 ]]; then
	nonce=$(python3 - "$tmp/panel-profile.html" <<'PY'
import re, sys
text = open(sys.argv[1], encoding='utf-8').read()
match = re.search(r'name="profile_nonce" value="([^"]+)"', text)
print(match.group(1) if match else '')
PY
)
	if [[ -z "$nonce" ]]; then
		fail 'profile nonce was not found'
	else
		response=$(curl -sS --max-time 30 -c "$cookie" -b "$cookie" -X POST "$base/wp-admin/admin-ajax.php" \
			--data-urlencode action=save_user_profile --data-urlencode security="$nonce" \
			--data-urlencode 'first_name_fa=کاربر' --data-urlencode 'last_name_fa=آزمایشی' \
			--data-urlencode 'first_name_en=Test' --data-urlencode 'last_name_en=User' \
			--data-urlencode 'gender=male' --data-urlencode 'birth_date=۱۳۷۰/۰۱/۰۹')
		requests=$((requests + 1))
		[[ "$response" == *'"success":true'* ]] || fail 'valid Persian profile data was rejected'
		fetch '/panel/profile/' "$tmp/profile-after.html"
		require_text "$tmp/profile-after.html" 'name="birth_date" class="form-control" inputmode="numeric" autocomplete="bday" value="1370/01/09"' 'Persian birth date was not normalized and persisted'

		invalid=$(curl -sS --max-time 30 -c "$cookie" -b "$cookie" -X POST "$base/wp-admin/admin-ajax.php" \
			--data-urlencode action=save_user_profile --data-urlencode security="$nonce" \
			--data-urlencode 'birth_date=۱۳۷۰/۱۹/۹۹')
		requests=$((requests + 1))
		[[ "$invalid" == *'"success":false'* ]] || fail 'invalid birth date was not rejected'
	fi
fi

fetch '/wp-admin/admin.php?page=evented-theme-settings' "$tmp/settings.html"
require_text "$tmp/settings.html" 'evented-update-card' 'theme settings/update card did not render (admin session required)'

# مرکز گزارش‌گیری داخلی: داده، فیلتر، asset و خروجی server-side.
fetch '/wp-admin/admin.php?page=evented-reports' "$tmp/reports-overview.html"
require_text "$tmp/reports-overview.html" 'مرکز یکپارچهٔ گزارش دوره‌ها و آزمون‌های LearnDash' 'reports overview did not render'
require_text "$tmp/reports-overview.html" 'گزارش تکمیل دوره‌ها' 'course report card is missing'
fetch '/wp-admin/admin.php?page=evented-reports-courses' "$tmp/reports-courses.html"
require_text "$tmp/reports-courses.html" 'دورهٔ گزارش آزمایشی' 'completed course fixture is missing from reports'
require_text "$tmp/reports-courses.html" 'reports.css?ver=1.0.0' 'reports stylesheet was not enqueued'
require_text "$tmp/reports-courses.html" 'خروجی CSV برای Excel' 'server-side CSV action is missing'
fetch '/wp-admin/admin.php?page=evented-reports-quizzes&quiz_id=999999' "$tmp/reports-quiz-empty.html"
require_text "$tmp/reports-quiz-empty.html" 'نتیجه‌ای مطابق فیلترها پیدا نشد' 'quiz ID filter did not produce an empty result'
fetch '/wp-admin/admin.php?page=evented-reports-quizzes' "$tmp/reports-quizzes.html"
require_text "$tmp/reports-quizzes.html" 'آزمون گزارش آزمایشی' 'quiz fixture is missing from reports'
require_text "$tmp/reports-quizzes.html" '203.0.113.10' 'quiz IP is missing from reports'
require_text "$tmp/reports-quizzes.html" '80' 'quiz score is missing from reports'
report_csv_url=$(python3 - "$tmp/reports-courses.html" <<'PY'
from html.parser import HTMLParser
import html,sys
class P(HTMLParser):
    url=''
    def handle_starttag(self,tag,attrs):
        href=dict(attrs).get('href','')
        if tag=='a' and 'action=evented_export_report' in href and 'format=csv' in href and not self.url: self.url=html.unescape(href)
p=P();p.feed(open(sys.argv[1],encoding='utf-8').read());print(p.url)
PY
)
if [[ -n "$report_csv_url" ]]; then
	code=$(curl -sS --max-time 60 -c "$cookie" -b "$cookie" -o "$tmp/report.csv" -w '%{http_code}' "$report_csv_url") || code=000
	requests=$((requests + 1)); [[ "$code" == 200 ]] || fail "report CSV export returned HTTP $code"
	require_text "$tmp/report.csv" 'دورهٔ گزارش آزمایشی' 'CSV export does not contain the filtered course data'
else
	fail 'signed report CSV URL is missing'
fi
report_json_url=$(python3 - "$tmp/reports-quizzes.html" <<'PY'
from html.parser import HTMLParser
import html,sys
class P(HTMLParser):
    url=''
    def handle_starttag(self,tag,attrs):
        href=dict(attrs).get('href','')
        if tag=='a' and 'action=evented_export_report' in href and 'format=json' in href and not self.url: self.url=html.unescape(href)
p=P();p.feed(open(sys.argv[1],encoding='utf-8').read());print(p.url)
PY
)
if [[ -n "$report_json_url" ]]; then
	code=$(curl -sS --max-time 60 -c "$cookie" -b "$cookie" -o "$tmp/report.json" -w '%{http_code}' "$report_json_url") || code=000
	requests=$((requests + 1)); [[ "$code" == 200 ]] || fail "report JSON export returned HTTP $code"
	if ! python3 - "$tmp/report.json" <<'PY'
import json,sys
report=json.load(open(sys.argv[1],encoding='utf-8'))
raise SystemExit(0 if report.get('rows') and any('آزمون گزارش آزمایشی' in row for row in report['rows']) else 1)
PY
	then fail 'JSON export is invalid or missing quiz data'; fi
else
	fail 'signed report JSON URL is missing'
fi
fetch '/wp-admin/admin.php?page=evented-reports-quizzes&passed=no' "$tmp/reports-quiz-failed.html"
require_text "$tmp/reports-quiz-failed.html" 'نتیجه‌ای مطابق فیلترها پیدا نشد' 'quiz pass/fail filter is incorrect'
fetch '/card-number-test/' "$tmp/card-number.html"
require_text "$tmp/card-number.html" '6037 **** **** 5678' 'legacy card-number shortcode did not render saved value'
if [[ -n "$report_csv_url" ]]; then
	code=$(curl -sS --max-time 60 -o "$tmp/report-unauthorized.txt" -w '%{http_code}' "$report_csv_url") || code=000
	requests=$((requests + 1))
	if grep -Fq 'آزمون گزارش آزمایشی' "$tmp/report-unauthorized.txt"; then fail 'report export exposed data without authentication'; fi
fi

# همهٔ assetهای محلی قالب که در HTML صفحهٔ خانه کشف شده‌اند باید 200 باشند.
python3 - "$tmp/home.html" <<'PY' > "$tmp/assets.txt"
import html, re, sys
text = open(sys.argv[1], encoding='utf-8').read()
urls = {html.unescape(u) for u in re.findall(r'''(?:src|href)=["']([^"']+/wp-content/themes/evented-edu/[^"']+)["']''', text)}
for url in sorted(urls): print(url)
PY
while IFS= read -r url; do
	[[ -n "$url" ]] || continue
	code=$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' "$url") || code=000
	requests=$((requests + 1))
	[[ "$code" == 200 ]] || fail "asset returned HTTP $code: $url"
done < "$tmp/assets.txt"

if [[ -n "$debug_log" && -f "$debug_log" ]]; then
	# هشدارهای هسته/Playground جدا از قالب‌اند؛ فقط stack/path خود قالب fail می‌شود.
	if grep -Ei 'PHP (Fatal error|Parse error|Warning|Deprecated|Notice).*wp-content/themes/evented-edu' "$debug_log" > "$tmp/theme-errors.txt"; then
		cat "$tmp/theme-errors.txt" >&2
		fail 'theme errors found in debug.log'
	fi
fi

if (( failures > 0 )); then
	printf 'Smoke test failed: %d issue(s), %d requests.\n' "$failures" "$requests" >&2
	exit 1
fi
printf 'WordPress smoke test passed: %d requests, 8 panel/admin routes, conditional assets and debug log.\n' "$requests"
