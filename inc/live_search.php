<?php
/**
 * جستجوی زندهٔ ایجکسی هدر (Live Search)
 *
 * - اندپوینت REST: GET /wp-json/evented/v1/search?q=…&scope=…
 * - ۵ نتیجهٔ برتر با تصویر، نوع، دسته و لینک «مشاهدهٔ همهٔ نتایج»؛
 * - کش موقت ۱۰ دقیقه‌ای برای هر (عبارت، دامنه)؛
 * - محدودیت طول عبارت و پاک‌سازی ورودی.
 *
 * فایل‌های همراه: assets/js/newhome/ee-live-search.js و assets/css/newhome/ee-live-search.css
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * بارگذاری دارایی‌ها روی همهٔ نماهای ee-*.
 */
add_action('wp_enqueue_scripts', static function () {
	if (!function_exists('evented_is_ee_view') || !evented_is_ee_view()) {
		return;
	}
	wp_enqueue_style('ee-live-search', PATH_DIR_URL . '/assets/css/newhome/ee-live-search.css', array('ee-shell'), '1.0.0');
	wp_enqueue_script('ee-live-search', PATH_DIR_URL . '/assets/js/newhome/ee-live-search.js', array(), '1.0.0', true);
	wp_localize_script('ee-live-search', 'eeLiveSearch', array(
		'endpoint' => esc_url_raw(rest_url('evented/v1/search')),
		'nonce'    => wp_create_nonce('wp_rest'),
		'search'   => esc_url_raw(home_url('/')),
		'min'      => 2,
		'i18n'     => array(
			'typing'  => 'برای جستجو تایپ کنید…',
			'loading' => 'در حال جستجو…',
			'empty'   => 'نتیجه‌ای پیدا نشد.',
			'all'     => 'مشاهدهٔ همهٔ نتایج',
			'error'   => 'خطا در برقراری ارتباط.',
		),
	));
}, 21);

/**
 * ثبت مسیر REST.
 */
add_action('rest_api_init', static function () {
	register_rest_route('evented/v1', '/search', array(
		'methods'             => WP_REST_Server::READABLE,
		'permission_callback' => '__return_true',
		'callback'            => 'evented_live_search_handler',
		'args'                => array(
			'q'     => array('type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'),
			'scope' => array('type' => 'string', 'required' => false, 'default' => '', 'sanitize_callback' => 'sanitize_key'),
		),
	));
});

/**
 * برچسب فارسی نوع محتوا.
 */
function evented_live_search_type_label($post_type)
{
	$scopes = function_exists('evented_search_scopes') ? evented_search_scopes() : array();
	if (isset($scopes[$post_type])) {
		return (string) $scopes[$post_type];
	}
	$obj = get_post_type_object($post_type);
	return $obj ? (string) $obj->labels->singular_name : (string) $post_type;
}

/**
 * آیکن هر نوع محتوا (نام آیکن اسپرایت).
 */
function evented_live_search_type_icon($post_type)
{
	$map = array(
		'post'         => 'article',
		'sfwd-courses' => 'school',
		'page'         => 'description',
		'video'        => 'smart_display',
		'podcast'      => 'podcasts',
		'library'      => 'local_library',
		'gallery'      => 'photo_library',
		'downloads'    => 'download',
	);
	$map = (array) apply_filters('evented_live_search_type_icons', $map);
	return isset($map[$post_type]) ? $map[$post_type] : 'search';
}

/**
 * پاسخ‌دهندهٔ REST.
 */
function evented_live_search_handler(WP_REST_Request $req)
{
	$q     = trim((string) $req->get_param('q'));
	$scope = (string) $req->get_param('scope');

	if (function_exists('mb_strlen') ? mb_strlen($q) < 2 : strlen($q) < 2) {
		return rest_ensure_response(array('items' => array(), 'total' => 0, 'more' => ''));
	}
	if (function_exists('mb_substr')) {
		$q = mb_substr($q, 0, 80);
	}

	$scopes = function_exists('evented_search_scopes') ? evented_search_scopes() : array('' => 'همه‌جا', 'post' => 'مقالات');
	if (!array_key_exists($scope, $scopes)) {
		$scope = '';
	}
	$types = '' !== $scope ? array($scope) : (function_exists('evented_search_post_types') ? evented_search_post_types() : array('post', 'sfwd-courses', 'page'));

	$key   = 'ee_ls_' . md5($q . '|' . $scope . '|' . implode(',', $types));
	$cache = get_transient($key);
	if (is_array($cache)) {
		return rest_ensure_response($cache);
	}

	$limit = (int) apply_filters('evented_live_search_limit', 5);
	$query = new WP_Query(array(
		's'                   => $q,
		'post_type'           => $types,
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
		'orderby'             => 'relevance',
	));

	$items = array();
	foreach ($query->posts as $p) {
		$type  = (string) $p->post_type;
		$thumb = has_post_thumbnail($p) ? (string) get_the_post_thumbnail_url($p, 'thumbnail') : '';
		$cat   = '';
		if ('post' === $type) {
			$c = get_the_category($p->ID);
			$cat = !empty($c) ? (string) $c[0]->name : '';
		} elseif ('sfwd-courses' === $type) {
			$t = get_the_terms($p->ID, 'ld_course_category');
			$cat = (!is_wp_error($t) && !empty($t)) ? (string) $t[0]->name : '';
		} else {
			$tax = get_object_taxonomies($type);
			if (!empty($tax)) {
				$t = get_the_terms($p->ID, $tax[0]);
				$cat = (!is_wp_error($t) && !empty($t)) ? (string) $t[0]->name : '';
			}
		}
		$meta = '';
		if ('sfwd-courses' === $type && function_exists('evented_course_pricing')) {
			$pr   = evented_course_pricing($p->ID);
			$meta = !empty($pr['is_free']) ? 'رایگان' : (string) $pr['price'];
		} elseif (function_exists('evented_post_date')) {
			$meta = (string) evented_post_date($p->ID);
		}
		$items[] = array(
			'id'    => (int) $p->ID,
			'title' => html_entity_decode(wp_strip_all_tags(get_the_title($p)), ENT_QUOTES, 'UTF-8'),
			'url'   => (string) get_permalink($p),
			'thumb' => $thumb,
			'type'  => evented_live_search_type_label($type),
			'icon'  => evented_live_search_type_icon($type),
			'cat'   => $cat,
			'meta'  => $meta,
		);
	}

	$more = add_query_arg(array('s' => rawurlencode($q), 'post_type' => $scope), home_url('/'));
	$data = array(
		'items' => $items,
		'total' => (int) $query->found_posts,
		'more'  => '' !== $scope ? $more : add_query_arg('s', rawurlencode($q), home_url('/')),
		'q'     => $q,
	);
	set_transient($key, $data, 10 * MINUTE_IN_SECONDS);

	$resp = rest_ensure_response($data);
	$resp->header('Cache-Control', 'public, max-age=300');
	return $resp;
}

/**
 * پاک‌سازی کش جستجو هنگام انتشار/ویرایش محتوا.
 */
add_action('save_post', static function ($post_id) {
	if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return;
	}
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_ee\_ls\_%' OR option_name LIKE '\_transient\_timeout\_ee\_ls\_%'"); // phpcs:ignore
});
