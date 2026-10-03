#!/usr/bin/env bash
# ساخت و اعتبارسنجی بستهٔ استاندارد GitHub Release برای آپدیت خودکار قالب.
# این بسته عمداً vendor/mPDF را نیز دارد؛ GitHub source zip به‌تنهایی قابل استقرار نیست.
# استفاده: bash bin/build-theme-release.sh [output.zip]
set -euo pipefail
cd "$(dirname "$0")/.."
root=$(pwd)

version=$(sed -nE 's/^Version:[[:space:]]*([^[:space:]]+).*/\1/p' style.css | head -1)
if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+([+-][0-9A-Za-z.-]+)?$ ]]; then
	echo "Invalid or missing theme version in style.css: $version" >&2
	exit 1
fi

output="${1:-$root/evented-edu.zip}"
mkdir -p "$(dirname "$output")"
output=$(cd "$(dirname "$output")" && pwd)/$(basename "$output")
rm -f "$output"

# vendor خارج از Git می‌ماند، اما باید حتماً داخل artifact نصب قالب باشد.
if [[ ! -f vendor/autoload.php || ! -f vendor/mpdf/mpdf/src/Mpdf.php ]]; then
	command -v composer >/dev/null 2>&1 || {
		echo 'Composer is required to build the deployable theme package with mPDF.' >&2
		exit 1
	}
	composer install --no-dev --classmap-authoritative --no-interaction --prefer-dist --no-progress
fi

stage=$(mktemp -d)
cleanup() { rm -rf "$stage"; }
trap cleanup EXIT
mkdir -p "$stage/evented-edu"
git archive --format=tar HEAD | tar -xf - -C "$stage/evented-edu"
cp -a vendor "$stage/evented-edu/vendor"
(
	cd "$stage"
	zip -q -r "$output" evented-edu
)
unzip -tqq "$output"

entries=$(unzip -Z1 "$output")
if grep -Ev '^evented-edu/' <<<"$entries" | grep -q .; then
	echo 'Package contains files outside the evented-edu root.' >&2
	exit 1
fi
for required in \
	style.css functions.php inc/includes.php inc/theme_updater.php \
	assets/fonts/Vazirmatn-Regular.ttf assets/fonts/Vazirmatn-Bold.ttf \
	vendor/autoload.php vendor/mpdf/mpdf/src/Mpdf.php; do
	if ! grep -Fxq "evented-edu/$required" <<<"$entries"; then
		echo "Required package file is missing: $required" >&2
		exit 1
	fi
done

packaged_style=$(unzip -p "$output" evented-edu/style.css)
if ! grep -Eq "^Version:[[:space:]]*$version[[:space:]]*$" <<<"$packaged_style"; then
	echo 'Packaged style.css version does not match the source version.' >&2
	exit 1
fi
if ! grep -Eq '^Update URI:[[:space:]]*https://github.com/ahmad75naraghi/learndash-theme[[:space:]]*$' <<<"$packaged_style"; then
	echo 'Packaged style.css is missing the expected Update URI.' >&2
	exit 1
fi
if command -v php >/dev/null 2>&1; then
	php -r "require '$stage/evented-edu/vendor/autoload.php'; exit(class_exists('Mpdf\\Mpdf') ? 0 : 1);" || {
		echo 'Packaged Composer autoloader cannot load mPDF.' >&2
		exit 1
	}
fi

printf 'Release package OK: %s (version %s, %s files)\n' "$output" "$version" "$(wc -l <<<"$entries" | tr -d ' ')"
