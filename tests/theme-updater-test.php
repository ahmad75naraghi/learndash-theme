<?php
/**
 * Unit/integration-lite test for inc/theme_updater.php without booting WordPress.
 * Run: php tests/theme-updater-test.php
 */

define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);

$GLOBALS['ee_test_filters'] = array();
function add_filter($tag, $callback) { $GLOBALS['ee_test_filters'][$tag][] = $callback; }
function add_action($tag, $callback) { $GLOBALS['ee_test_filters'][$tag][] = $callback; }
function apply_filters($tag, $value) { return $value; }
function get_template_directory() { return '/wordpress/wp-content/themes/evented-edu'; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function trailingslashit($value) { return rtrim($value, '/\\') . '/'; }
function untrailingslashit($value) { return rtrim($value, '/\\'); }

class WP_Error {
	public $code;
	public function __construct($code) { $this->code = $code; }
}

require dirname(__DIR__) . '/inc/theme_updater.php';

$failures = array();
$assert = static function ($condition, $message) use (&$failures) {
	if (!$condition) {
		$failures[] = $message;
	}
};

$base = array(
	'tag_name'     => 'v2.4.1',
	'draft'        => false,
	'prerelease'   => false,
	'html_url'     => 'https://github.com/ahmad75naraghi/learndash-theme/releases/tag/v2.4.1',
	'zipball_url'  => 'https://api.github.com/repos/ahmad75naraghi/learndash-theme/zipball/v2.4.1',
	'body'         => "Changes\n- tested",
	'published_at' => '2026-09-28T00:00:00Z',
	'assets'       => array(
		array(
			'name'                 => 'evented-edu.zip',
			'browser_download_url' => 'https://github.com/ahmad75naraghi/learndash-theme/releases/download/v2.4.1/evented-edu.zip',
		),
	),
);

$release = evented_theme_updater_normalize_release($base);
$assert(is_array($release), 'A valid stable release must normalize.');
$assert('2.4.1' === $release['version'], 'The leading v must be removed from the version.');
$assert(false !== strpos($release['package'], '/evented-edu.zip'), 'The canonical release asset must have priority.');

$fallback = $base;
$fallback['assets'] = array();
$fallback_release = evented_theme_updater_normalize_release($fallback);
$assert($fallback_release && $fallback_release['package'] === $base['zipball_url'], 'zipball_url must be used when the canonical asset is absent.');

$draft = $base;
$draft['draft'] = true;
$assert(null === evented_theme_updater_normalize_release($draft), 'Draft releases must never be offered.');
$prerelease = $base;
$prerelease['prerelease'] = true;
$assert(null === evented_theme_updater_normalize_release($prerelease), 'Prereleases must never be offered.');
$evil = $base;
$evil['assets'][0]['browser_download_url'] = 'https://evil.example/evented-edu.zip';
$evil['zipball_url'] = 'https://evil.example/fallback.zip';
$assert(null === evented_theme_updater_normalize_release($evil), 'Packages outside official GitHub hosts must be rejected.');

$update = evented_theme_updater_build_update($release, '2.1.0', 'evented-edu');
$assert(is_array($update) && '2.4.1' === $update['new_version'], 'A newer release must create a WordPress update response.');
$assert(null === evented_theme_updater_build_update($release, '2.4.1', 'evented-edu'), 'The installed version must not update to itself.');
$assert(null === evented_theme_updater_build_update($release, '2.5.0', 'evented-edu'), 'Downgrades must never be offered.');

/* Simulate the random root directory produced by a GitHub zipball. */
$callbacks = isset($GLOBALS['ee_test_filters']['upgrader_source_selection']) ? $GLOBALS['ee_test_filters']['upgrader_source_selection'] : array();
$source_callback = end($callbacks);
$assert(is_callable($source_callback), 'The source normalization hook must be registered.');

$tmp = sys_get_temp_dir() . '/evented-update-test-' . uniqid('', true);
$remote = $tmp . '/unpacked/';
$source = $remote . 'ahmad75naraghi-learndash-theme-abcdef/';
mkdir($source, 0777, true);
file_put_contents($source . 'style.css', "/*\nTheme Name: evented-edu\nVersion: 2.4.1\n*/\n");

$wp_filesystem = new class {
	public function exists($path) { return file_exists($path); }
	public function delete($path, $recursive = false) {
		if (!file_exists($path)) { return true; }
		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($it as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
		return rmdir($path);
	}
	public function move($source, $target, $overwrite = false) { return rename($source, $target); }
};

if (is_callable($source_callback)) {
	$normalized = $source_callback($source, $remote, null, array('type' => 'theme', 'theme' => 'evented-edu'));
	$assert(!($normalized instanceof WP_Error), 'Source normalization must not return WP_Error for a valid package.');
	$assert('evented-edu' === basename(untrailingslashit($normalized)), 'GitHub random package root must become evented-edu.');
	$assert(file_exists(trailingslashit($normalized) . 'style.css'), 'The normalized update source must retain style.css.');
}
$wp_filesystem->delete($tmp, true);

$style = file_get_contents(dirname(__DIR__) . '/style.css');
$assert(1 === preg_match('/^Version:\s*2\.4\.1\s*$/m', $style), 'style.css must advertise version 2.4.1.');
$assert(1 === preg_match('#^Update URI:\s*https://github\.com/ahmad75naraghi/learndash-theme\s*$#m', $style), 'style.css must have the GitHub Update URI.');

if ($failures) {
	foreach ($failures as $failure) {
		fwrite(STDERR, "FAIL: {$failure}\n");
	}
	exit(1);
}

echo "Theme updater tests passed (release parsing, security, version comparison, GitHub package root).\n";
