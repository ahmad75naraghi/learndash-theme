#!/usr/bin/env bash
# بررسی سریع سلامت syntax قالب: PHP (php -l)، JS (node --check)، CSS (توازن آکولاد).
# استفاده: bash bin/lint.sh            (از ریشهٔ قالب)
#          PHP_BIN=/path/to/php bash bin/lint.sh
set -u
cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-php}"
fail=0

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then echo "PHP binary not found: $PHP_BIN (set PHP_BIN=...)"; exit 2; fi
echo "== PHP ($($PHP_BIN -v 2>/dev/null | head -1))"
while IFS= read -r f; do
	out=$("$PHP_BIN" -l "$f" 2>&1)
	if ! grep -q "No syntax errors" <<<"$out"; then
		echo "FAIL $f"; echo "$out" | head -3; fail=1
	fi
done < <(git ls-files '*.php')

echo "== JS"
while IFS= read -r f; do
	case "$f" in *.min.js) continue;; esac
	if ! node --check "$f" 2>/tmp/js_err; then
		echo "FAIL $f"; head -3 /tmp/js_err; fail=1
	fi
done < <(git ls-files '*.js')

echo "== CSS brace balance"
while IFS= read -r f; do
	node -e '
		const fs=require("fs");const f=process.argv[1];
		const s=fs.readFileSync(f,"utf8").replace(/\/\*[\s\S]*?\*\//g,"");
		const o=(s.match(/{/g)||[]).length,c=(s.match(/}/g)||[]).length;
		if(o!==c){console.log("FAIL "+f+" { "+o+" } "+c);process.exit(1)}
	' "$f" || fail=1
done < <(git ls-files '*.css')

echo "== Forbidden patterns"
if git grep -n -I -E 'edu\.falnic\.com|themes/edu-falnic' -- ':!*.md' ':!bin/lint.sh' ; then
	echo "FAIL: hardcoded legacy domain/theme path"; fail=1
fi
if git grep -n -I -E '<(script|link)[^>]+(src|href)="https?://' -- '*.php' ':!bin/lint.sh' ; then
	echo "FAIL: external script/style tag (site must not make external requests)"; fail=1
fi

if [ "$fail" -eq 0 ]; then echo "ALL OK"; else echo "LINT FAILED"; fi
exit $fail
