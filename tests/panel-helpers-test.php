<?php
/** Lightweight deterministic tests for panel helpers. */
define('ABSPATH', __DIR__ . '/');
require dirname(__DIR__) . '/inc/panel_helpers.php';

$cases = array(
	'۱۳۷۰/۰۱/۰۹' => '1370/01/09',
	'١٤٠٥/٠٧/٠٦' => '1405/07/06',
	'1405/07/06' => '1405/07/06',
	'۰۹۱۲abc'     => '0912abc',
);
foreach ($cases as $input => $expected) {
	$actual = evented_normalize_digits($input);
	if ($actual !== $expected) {
		fwrite(STDERR, "Digit normalization failed: {$input} => {$actual}\n");
		exit(1);
	}
}
echo "Panel helper tests passed (Persian and Arabic digit normalization).\n";
