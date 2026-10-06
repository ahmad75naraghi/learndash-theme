#!/usr/bin/env bash
# تست واقعی جایگزینی قالب با WordPress Theme_Upgrader روی Playground disposable.
# استفاده: bash bin/test-wordpress-update.sh http://127.0.0.1:9400 /tmp/wp/site
set -euo pipefail

base="${1:-http://127.0.0.1:9400}"; base="${base%/}"
site="${2:-/tmp/wp/site}"
root=$(cd "$(dirname "$0")/.." && pwd)
package="$site/evented-edu-update-test.zip"
runner="$site/evented-update-integration.php"
cookie=$(mktemp)
response=$(mktemp)
cleanup() { rm -f "$cookie" "$response" "$package" "$runner"; }
trap cleanup EXIT

[[ -f "$site/wp-load.php" ]] || { echo "WordPress root not found: $site" >&2; exit 1; }
[[ -d "$site/wp-content/themes/evented-edu" ]] || { echo 'evented-edu theme copy not found.' >&2; exit 1; }

bash "$root/bin/build-theme-release.sh" "$package"
cp "$root/tests/wp-theme-update-integration.php" "$runner"

# ممیزی مرورگر می‌تواند worker تکی Playground را موقتاً restart کند؛ درخواست‌های مقدماتی retry می‌شوند.
curl_retry=(--retry 5 --retry-delay 2 --retry-all-errors)

# روی CI ورود صریح و در توسعه cookie ورود خودکار Playground؛ سپس nonce واقعی تنظیمات قالب.
if [[ -n "${SMOKE_USERNAME:-}" && -n "${SMOKE_PASSWORD:-}" ]]; then
	curl -sS "${curl_retry[@]}" --max-time 30 -c "$cookie" "$base/wp-login.php" -o /dev/null
	curl -sS "${curl_retry[@]}" --max-time 30 --max-redirs 10 -L -c "$cookie" -b "$cookie" \
		--data-urlencode "log=$SMOKE_USERNAME" --data-urlencode "pwd=$SMOKE_PASSWORD" \
		--data-urlencode 'wp-submit=Log In' --data-urlencode "redirect_to=$base/wp-admin/" \
		--data-urlencode 'testcookie=1' "$base/wp-login.php" -o /dev/null
	grep -Fq 'wordpress_logged_in_' "$cookie" || { echo 'Explicit WordPress login failed.' >&2; exit 1; }
fi
curl -sS "${curl_retry[@]}" --max-time 30 --max-redirs 10 -c "$cookie" -b "$cookie" -L "$base/evented-update-integration.php?nonce_only=1" -o "$response"
nonce=$(python3 - "$response" <<'PY'
import json, sys
try:
    print(json.load(open(sys.argv[1], encoding='utf-8')).get('nonce', ''))
except Exception:
    print('')
PY
)
[[ -n "$nonce" ]] || { echo 'Could not extract authenticated updater nonce.' >&2; exit 1; }

set +e
# خود Theme_Upgrader عملیات destructive است؛ endpoint نباید بعد از پاسخ 5xx به‌طور
# خودکار تکرار شود، چون تلاش نخست ممکن است جایگزینی را کامل کرده باشد.
curl -sS --fail-with-body --max-time 120 -c "$cookie" -b "$cookie" \
	-o "$response" "$base/evented-update-integration.php?_wpnonce=$nonce"
curl_status=$?
set -e
cat "$response"
printf '\n'
[[ "$curl_status" == 0 ]] || { echo "Theme_Upgrader integration request failed (curl $curl_status)." >&2; exit 1; }
python3 - "$response" <<'PY'
import json, sys
payload = json.load(open(sys.argv[1], encoding='utf-8'))
if not payload.get('success'):
    raise SystemExit('Theme update checks failed: ' + repr(payload))
failed = [key for key, value in payload.get('checks', {}).items() if not value]
if failed:
    raise SystemExit('Failed update checks: ' + ', '.join(failed))
print('WordPress Theme_Upgrader integration passed (' + ', '.join(payload['checks']) + ').')
PY
