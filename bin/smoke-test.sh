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
	grep -Fq 'wp-admin-bar-my-account' "$tmp/login.html" || fail 'explicit WordPress login failed'
fi

# درخواست اول cookie ورود خودکار Playground را می‌گیرد؛ روی staging باید cookie معتبر فراهم باشد.
fetch '/' "$tmp/home.html"
require_text "$tmp/home.html" 'id="ee-main"' 'home did not render the evented shell'
require_text "$tmp/home.html" ' defer src=' 'theme scripts are not deferred'
require_text "$tmp/home.html" 'Vazirmatn-Variable.woff2' 'main font preload is missing'

panel_paths=(panel panel/profile panel/my-courses panel/payments panel/wishlist panel/certificates panel/settings)
for path in "${panel_paths[@]}"; do
	name=${path//\//-}
	fetch "/$path/" "$tmp/$name.html"
	require_text "$tmp/$name.html" 'class="ee-panel' "panel shell missing on /$path/ (authenticated session required)"
	require_text "$tmp/$name.html" 'data-ee-panel-nav' "mobile panel navigation missing on /$path/"
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
