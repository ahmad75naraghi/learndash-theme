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
if ! python3 - "$tmp/home.html" <<'PY'
from pathlib import Path
import re
import sys
html = Path(sys.argv[1]).read_text(encoding='utf-8')
item = re.search(r'<div class="([^"]*\bee-nav-item\b[^"]*)"[^>]*>\s*<a href="[^"]*/videos/"', html)
raise SystemExit(0 if item and 'has-sub' not in item.group(1).split() else 1)
PY
then
	fail 'video navigation item must not have a taxonomy submenu'
fi

# CPTهای مهاجرت‌کرده بدون CPT UI باید آرشیو و نمای تکی سالم داشته باشند.
resource_paths=('lib/' 'clip/' 'gallery/' 'galery_cat/%DA%AF%D8%B2%D8%A7%D8%B1%D8%B4-%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%DB%8C/' 'lib/lib-item-1/' 'clip/clip-item-1/' 'gallery/gallery-item-1/')
for path in "${resource_paths[@]}"; do
	name=${path//\//-}
	fetch "/$path" "$tmp/resource-$name.html"
	require_text "$tmp/resource-$name.html" 'id="ee-main"' "custom content route did not render: /$path"
done
fetch '/wp-json/wp/v2/types/clip' "$tmp/clip-rest-type.json"
require_text "$tmp/clip-rest-type.json" '"slug":"clip"' 'clip REST type is missing'
reject_text "$tmp/clip-rest-type.json" 'wpdmcategory' 'clip remains associated with the library category taxonomy'
reject_text "$tmp/clip-rest-type.json" 'wpdmtag' 'clip remains associated with the library tag taxonomy'
require_text "$tmp/resource-lib-lib-item-1-.html" 'ee-resource-single' 'library single template did not render'
require_text "$tmp/resource-clip-clip-item-1-.html" 'ee-resource-single' 'video single template did not render'
reject_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-meta-cat"' 'video single must not render a taxonomy category'
reject_text "$tmp/resource-clip-.html" 'class="ee-arch-chips"' 'video archive inherited library taxonomy filters'
require_text "$tmp/resource-lib-.html" 'منابع کتابخانه' 'library taxonomy fixture did not render'
require_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-resource-data"' 'video metadata section did not render'
require_text "$tmp/resource-clip-clip-item-1-.html" '<code dir="ltr">_video_url</code>' 'legacy video meta key is missing'
require_text "$tmp/resource-clip-clip-item-1-.html" '/wp-content/uploads/video-1.mp4' 'video URL was not recovered from post meta'
require_text "$tmp/resource-clip-clip-item-1-.html" 'class="ee-resource-video-player"' 'direct video URL did not render a player'
require_text "$tmp/resource-clip-clip-item-1-.html" 'clip_playlist' 'nested playlist metadata is missing'
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
