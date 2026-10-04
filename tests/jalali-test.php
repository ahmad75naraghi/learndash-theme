<?php
/** Standalone contracts for the built-in Jalali engine. */
define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);
define('PATH_DIR_URL', 'https://example.test/theme');
function add_filter() {}
function wp_timezone() { return new DateTimeZone('Asia/Tehran'); }
function current_time($type) { return 'timestamp' === $type ? 1710880200 : ''; }
require dirname(__DIR__) . '/inc/jalali.php';
function check($condition, $message) { if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); } }
check(evented_jalali_from_gregorian(2024,3,20) === array(1403,1,1), 'Nowruz 1403 conversion');
check(evented_jalali_to_gregorian(1403,1,1) === array(2024,3,20), 'reverse Nowruz conversion');
check(evented_jalali_checkdate(12,30,1403), '1403 must be leap');
check(!evented_jalali_checkdate(12,30,1402), '1402 must not accept Esfand 30');
check(evented_jalali_parse('۱۴۰۳/۱۲/۳۰ ۲۳:۵۹')['minute'] === 59, 'Persian digit datetime parsing');
check(0 === evented_jalali_to_timestamp('۱۴۰۲/۱۲/۳۰'), 'invalid date must not produce timestamp');
$stamp = evented_jalali_to_timestamp('۱۴۰۳/۰۱/۰۱ ۱۲:۳۰');
check($stamp > 0 && evented_jalali_format($stamp,'Y/m/d H:i') === '1403/01/01 12:30', 'timezone-safe timestamp round trip');
check(evented_jalali_persian_digits('1405/07/12') === '۱۴۰۵/۰۷/۱۲', 'Persian output digits');
check(evented_jalali_is_machine_format('c') && !evented_jalali_is_machine_format('j F Y'), 'machine format guard');
echo "Jalali engine tests passed.\n";
