#!/usr/bin/env bash
# ساخت و اعتبارسنجی بستهٔ استاندارد GitHub Release برای آپدیت خودکار قالب.
# استفاده: bash bin/build-theme-release.sh [output.zip]
set -euo pipefail
cd "$(dirname "$0")/.."

version=$(sed -nE 's/^Version:[[:space:]]*([^[:space:]]+).*/\1/p' style.css | head -1)
if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+([+-][0-9A-Za-z.-]+)?$ ]]; then
	echo "Invalid or missing theme version in style.css: $version" >&2
	exit 1
fi

output="${1:-$(pwd)/evented-edu.zip}"
mkdir -p "$(dirname "$output")"
rm -f "$output"

git archive --format=zip --prefix=evented-edu/ HEAD -o "$output"
unzip -tqq "$output"

entries=$(unzip -Z1 "$output")
if grep -Ev '^evented-edu/' <<<"$entries" | grep -q .; then
	echo 'Package contains files outside the evented-edu root.' >&2
	exit 1
fi
for required in style.css functions.php inc/includes.php inc/theme_updater.php; do
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

printf 'Release package OK: %s (version %s, %s files)\n' "$output" "$version" "$(wc -l <<<"$entries" | tr -d ' ')"
