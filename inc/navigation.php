<?php
/**
 * ناوبری سایت — منوی استاتیک هدر/فوتر/کشوی موبایل + فیلتر جستجو + تب‌های مقالات خانه
 *
 * منو «استاتیک» است (از پیشخوان ساخته نمی‌شود) اما آدرس‌ها و زیرمنوها از
 * محتوای واقعی سایت (پست‌تایپ‌ها، دسته‌بندی‌ها، برگه‌ها) خوانده و **یک هفته** کش می‌شوند
 * تا هیچ سربار پرفورمنسی روی هر بازدید نداشته باشد.
 *
 * بخش‌های «کتابخانه، گالری، ویدیو، دانلودها، پادکست» توسط افزونه به‌صورت پست‌تایپ
 * ساخته می‌شوند؛ چون اسلاگ دقیق افزونه ممکن است متفاوت باشد، برای هر بخش چند اسلاگ
 * رایج امتحان می‌شود (قابل تغییر با فیلتر `evented_nav_post_type_candidates`).
 *
 * فیلترهای مفید:
 *   - evented_nav_items               : تغییر آیتم‌های نهایی منو
 *   - evented_nav_post_type_candidates: نگاشت کلید بخش → اسلاگ‌های احتمالی پست‌تایپ
 *   - evented_nav_cache_ttl           : مدت کش (پیش‌فرض یک هفته)
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/* نسخهٔ ساختار کش — با تغییر ساختار آرایه‌ها این عدد را بالا ببرید تا کش قدیمی نادیده گرفته شود. */
if (!defined('EVENTED_NAV_CACHE_VER')) {
	define('EVENTED_NAV_CACHE_VER', '4');
}

/**
 * مدت اعتبار کش‌های ناوبری (پیش‌فرض: یک هفته).
 *
 * @return int
 */
function evented_nav_cache_ttl()
{
	return (int) apply_filters('evented_nav_cache_ttl', WEEK_IN_SECONDS);
}

/**
 * نگاشت «کلید بخش» → اسلاگ‌های احتمالی پست‌تایپ (به ترتیب اولویت).
 *
 * @return array<string, string[]>
 */
function evented_nav_post_type_candidates()
{
	$map = array(
		'library'   => array('library', 'book', 'books', 'ebook', 'ebooks', 'ketab', 'ketabkhaneh', 'wp_library'),
		'gallery'   => array('gallery', 'galleries', 'photo', 'photos', 'album', 'albums', 'envira', 'foogallery'),
		'video'     => array('video', 'videos', 'clip', 'clips', 'film', 'movie'),
		'downloads' => array('download', 'downloads', 'dlm_download', 'edd_download', 'file', 'files', 'attachment_file'),
		'podcast'   => array('podcast', 'podcasts', 'episode', 'episodes', 'audio', 'seriously-simple-podcasting'),
		'courses'   => array('sfwd-courses'),
	);

	return (array) apply_filters('evented_nav_post_type_candidates', $map);
}

/**
 * پیدا کردن پست‌تایپ واقعی یک بخش از بین اسلاگ‌های احتمالی.
 *
 * @param string $key کلید بخش (library|gallery|…).
 * @return string اسلاگ پست‌تایپ یا رشتهٔ خالی.
 */
function evented_nav_find_post_type($key)
{
	$map = evented_nav_post_type_candidates();
	if (empty($map[$key])) {
		return '';
	}
	foreach ((array) $map[$key] as $slug) {
		if (post_type_exists($slug)) {
			$obj = get_post_type_object($slug);
			if ($obj && !empty($obj->public)) {
				return (string) $slug;
			}
		}
	}
	return '';
}

/**
 * ترم‌های یک تاکسونومی به‌صورت آیتم زیرمنو.
 *
 * @param string $taxonomy تاکسونومی.
 * @param int    $limit    حداکثر تعداد.
 * @return array<int, array{title:string,url:string,count:int}>
 */
function evented_nav_term_children($taxonomy, $limit = 12)
{
	if (!taxonomy_exists($taxonomy)) {
		return array();
	}

	$args = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => true,
		'number'     => (int) $limit,
		'orderby'    => 'count',
		'order'      => 'DESC',
	);
	if (is_taxonomy_hierarchical($taxonomy)) {
		$args['parent'] = 0;
	}

	$terms = get_terms($args);
	if (is_wp_error($terms) || empty($terms)) {
		return array();
	}

	$out = array();
	foreach ($terms as $term) {
		if (!$term instanceof WP_Term) {
			continue;
		}
		$link = get_term_link($term);
		if (is_wp_error($link)) {
			continue;
		}
		$out[] = array(
			'title' => (string) $term->name,
			'url'   => (string) $link,
			'count' => (int) $term->count,
		);
	}
	return $out;
}

/**
 * زیرمنوی یک پست‌تایپ: ترم‌های اولین تاکسونومی عمومی آن (سلسله‌مراتبی مقدم است).
 *
 * @param string $post_type پست‌تایپ.
 * @return array
 */
function evented_nav_post_type_children($post_type)
{
	$taxes = get_object_taxonomies($post_type, 'objects');
	if (empty($taxes)) {
		return array();
	}

	$pick = '';
	foreach ($taxes as $tax) {
		if (empty($tax->public) || empty($tax->show_ui)) {
			continue;
		}
		if (!empty($tax->hierarchical)) {
			$pick = $tax->name;
			break;
		}
		if ('' === $pick) {
			$pick = $tax->name;
		}
	}

	return $pick ? evented_nav_term_children($pick) : array();
}

/**
 * آدرس دستی یک بخش از «تنظیمات قالب → نشانی بخش‌ها» (nav_url_{key}).
 * مقدار می‌تواند نسبی (/lib/) یا کامل باشد؛ خالی = تشخیص خودکار.
 *
 * @param string $key کلید بخش.
 * @return string
 */
function evented_nav_manual_url($key)
{
	$v = function_exists('evented_opt') ? (string) evented_opt('nav_url_' . $key, '') : '';
	$v = trim($v);
	if ('' === $v) {
		return '';
	}
	if (0 === strpos($v, '/')) {
		return (string) home_url($v);
	}
	if (!preg_match('#^https?://#i', $v)) {
		return (string) home_url('/' . trim($v, '/') . '/');
	}
	return $v;
}

/**
 * آدرس یک برگه با امتحان چند اسلاگ؛ در نبود برگه، مسیر پیش‌فرض.
 *
 * @param string[] $slugs    اسلاگ‌های احتمالی برگه.
 * @param string   $fallback مسیر پیش‌فرض (نسبی، بدون اسلش ابتدایی).
 * @return array{url:string,page_id:int}
 */
function evented_nav_page_url($slugs, $fallback)
{
	foreach ((array) $slugs as $slug) {
		$page = get_page_by_path($slug);
		if (!$page instanceof WP_Post) {
			/* اسلاگ‌های فارسی: هم شکل خام و هم درصدی‌شده امتحان می‌شود */
			$page = get_page_by_path(rawurlencode($slug));
		}
		if ($page instanceof WP_Post && 'publish' === $page->post_status) {
			return array('url' => (string) get_permalink($page), 'page_id' => (int) $page->ID);
		}
	}
	return array('url' => (string) home_url('/' . trim($fallback, '/') . '/'), 'page_id' => 0);
}

/**
 * ساخت آیتم یک بخشِ مبتنی بر پست‌تایپ (کتابخانه، ویدیو، …).
 *
 * اولویت آدرس: بایگانی پست‌تایپ → برگهٔ هم‌نام → مسیر پیش‌فرض.
 *
 * @param string   $key        کلید بخش.
 * @param string   $title      عنوان فارسی.
 * @param string   $icon       آیکن Material.
 * @param string[] $page_slugs اسلاگ‌های احتمالی برگه (fallback).
 * @return array
 */
function evented_nav_build_cpt_item($key, $title, $icon, $page_slugs)
{
	$post_type = evented_nav_find_post_type($key);
	$children  = array();
	$url       = evented_nav_manual_url($key);
	$page_id   = 0;

	if ('' !== $post_type) {
		$children = evented_nav_post_type_children($post_type);
	}

	/* اولویت: آدرس دستی → برگهٔ هم‌نام (shamiim.ir بخش‌ها را با برگه می‌سازد) → بایگانی پست‌تایپ → مسیر پیش‌فرض */
	if ('' === $url) {
		$resolved = evented_nav_page_url($page_slugs, '');
		if ($resolved['page_id']) {
			$url     = $resolved['url'];
			$page_id = $resolved['page_id'];
		}
	}
	if ('' === $url && '' !== $post_type) {
		$archive = get_post_type_archive_link($post_type);
		if ($archive) {
			$url = (string) $archive;
		}
	}
	if ('' === $url) {
		$url = (string) home_url('/' . trim($page_slugs[0], '/') . '/');
	}

	return array(
		'key'       => $key,
		'title'     => $title,
		'url'       => $url,
		'icon'      => $icon,
		'post_type' => $post_type,
		'page_id'   => $page_id,
		'children'  => $children,
	);
}

/**
 * ساخت آرایهٔ کامل منو (بدون کش).
 *
 * @return array<int, array>
 */
function evented_nav_build_items()
{
	$items = array();

	/* صفحه اصلی */
	$items[] = array(
		'key' => 'home', 'title' => 'صفحه اصلی', 'url' => (string) home_url('/'),
		'icon' => 'home', 'post_type' => '', 'page_id' => 0, 'children' => array(),
	);

	/* مقالات → برگهٔ نوشته‌ها + دسته‌بندی‌های وبلاگ */
	$posts_page = (int) get_option('page_for_posts');
	$blog_url   = evented_nav_manual_url('articles');
	if ('' === $blog_url) {
		$blog_url = $posts_page ? (string) get_permalink($posts_page) : '';
	}
	if ('' === $blog_url) {
		$resolved = evented_nav_page_url(array('blogs', 'blog', 'articles', 'maghalat'), 'blogs');
		$blog_url = $resolved['url'];
	}
	$items[] = array(
		'key' => 'articles', 'title' => 'مقالات', 'url' => $blog_url, 'icon' => 'article',
		'post_type' => 'post', 'page_id' => $posts_page, 'children' => evented_nav_term_children('category'),
	);

	/* اسلاگ‌های واقعی shamiim.ir در اولویت، سپس اسلاگ‌های رایج */
	$items[] = evented_nav_build_cpt_item('library', 'کتابخانه', 'local_library', array('lib', 'library', 'کتابخانه', 'books', 'ketabkhaneh'));
	$items[] = evented_nav_build_cpt_item('gallery', 'گالری', 'photo_library', array('gallery', 'گالری-مناسبتی', 'گالری-موضوعی', 'galleries'));
	$items[] = evented_nav_build_cpt_item('video', 'ویدیو', 'smart_display', array('videos', 'video'));
	$items[] = evented_nav_build_cpt_item('downloads', 'دانلودها', 'download_for_offline', array('download', 'downloads'));

	/* دوره‌ها → بایگانی لرن‌دش /courses/ + دسته‌های دوره */
	$courses_url = evented_nav_manual_url('courses');
	if ('' === $courses_url && post_type_exists('sfwd-courses')) {
		$courses_url = (string) get_post_type_archive_link('sfwd-courses');
	}
	if ('' === $courses_url) {
		$resolved    = evented_nav_page_url(array('courses', 'all-courses-2', 'all-courses'), 'courses');
		$courses_url = $resolved['url'];
	}
	$items[] = array(
		'key' => 'courses', 'title' => 'دوره‌ها', 'url' => $courses_url, 'icon' => 'school',
		'post_type' => post_type_exists('sfwd-courses') ? 'sfwd-courses' : '', 'page_id' => 0,
		'children' => post_type_exists('sfwd-courses') ? evented_nav_term_children('ld_course_category') : array(),
	);

	$items[] = evented_nav_build_cpt_item('podcast', 'پادکست', 'podcasts', array('podcast', 'player', 'podcasts'));

	/* برگه‌های ثابت */
	$static = array(
		array('ramadan',  'ویژه رمضان',   'nights_stay',    array('ویژه-رمضان', 'ramezan', 'ramadan', 'ramazan', 'ramadan-special')),
		array('contests', 'مسابقات',      'emoji_events',   array('match', 'competitions', 'contests', 'contest', 'competition')),
		array('about',    'معرفی سایت',   'info',           array('درباره-ما', 'about-2', 'about', 'about-us', 'introduction')),
		array('contact',  'ارتباط با ما', 'support_agent',  array('contact', 'contact-us', 'ertebat', 'tamas')),
	);
	foreach ($static as $row) {
		list($key, $title, $icon, $slugs) = $row;
		$manual   = evented_nav_manual_url($key);
		$resolved = $manual ? array('url' => $manual, 'page_id' => 0) : evented_nav_page_url($slugs, $slugs[0]);
		$items[]  = array(
			'key' => $key, 'title' => $title, 'url' => $resolved['url'], 'icon' => $icon,
			'post_type' => '', 'page_id' => $resolved['page_id'], 'children' => array(),
		);
	}

	return $items;
}

/**
 * آیتم‌های منوی اصلی (کش‌شده به مدت یک هفته).
 *
 * @return array<int, array{key:string,title:string,url:string,icon:string,post_type:string,page_id:int,children:array}>
 */
function evented_nav_items()
{
	static $memo = null;
	if (null !== $memo) {
		return $memo;
	}

	$cache_key = 'evented_nav_items_v' . EVENTED_NAV_CACHE_VER;
	$items     = get_transient($cache_key);

	if (!is_array($items) || empty($items)) {
		$items = evented_nav_build_items();
		set_transient($cache_key, $items, evented_nav_cache_ttl());
	}

	/**
	 * آیتم‌های نهایی منو (پس از کش).
	 *
	 * @param array $items
	 */
	$memo = (array) apply_filters('evented_nav_items', $items);
	return $memo;
}

/**
 * یک آیتم منو با کلید مشخص.
 *
 * @param string $key کلید.
 * @return array|null
 */
function evented_nav_item($key)
{
	foreach (evented_nav_items() as $item) {
		if (isset($item['key']) && $item['key'] === $key) {
			return $item;
		}
	}
	return null;
}

/**
 * آدرس یک بخش منو (میان‌بر برای قالب‌ها).
 *
 * @param string $key کلید.
 * @return string
 */
function evented_nav_url($key)
{
	$item = evented_nav_item($key);
	return $item ? (string) $item['url'] : (string) home_url('/');
}

/**
 * پاک کردن کش‌های ناوبری (منو + تب‌های مقالات خانه).
 *
 * @param bool $tabs_too تب‌های صفحهٔ اصلی هم پاک شود؟
 * @return void
 */
function evented_nav_flush_cache($tabs_too = true)
{
	delete_transient('evented_nav_items_v' . EVENTED_NAV_CACHE_VER);
	if ($tabs_too) {
		delete_transient('evented_home_tabs_v' . EVENTED_NAV_CACHE_VER);
		delete_transient('evented_home_course_tabs');
	}
}

/* انتشار/ویرایش/حذف دوره → تب‌های دورهٔ صفحهٔ اصلی و مگامنو تازه شود. */
function evented_flush_course_caches() {
	delete_transient('evented_home_course_tabs');
	delete_transient('evented_nav_course_mega');
}
add_action('save_post_sfwd-courses', 'evented_flush_course_caches');
add_action('deleted_post', function ($post_id) {
	if ('sfwd-courses' === get_post_type($post_id)) { evented_flush_course_caches(); }
});
add_action('set_object_terms', function ($object_id) {
	if ('sfwd-courses' === get_post_type($object_id)) { evented_flush_course_caches(); }
});

/**
 * دادهٔ مگامنوی «دوره‌ها»: هر دستهٔ دوره یک تب با حداکثر ۴ دوره (کش ۱۲ ساعته).
 *
 * @return array<int,array{id:int,name:string,url:string,count:int,courses:array}>
 */
function evented_nav_course_mega()
{
	if (!post_type_exists('sfwd-courses') || !taxonomy_exists('ld_course_category')) {
		return array();
	}
	$cached = get_transient('evented_nav_course_mega');
	if (is_array($cached)) {
		return $cached;
	}
	$terms = get_terms(array('taxonomy' => 'ld_course_category', 'hide_empty' => true, 'number' => 8, 'orderby' => 'count', 'order' => 'DESC', 'parent' => 0));
	$tabs  = array();
	if (!is_wp_error($terms)) {
		foreach ($terms as $term) {
			$link = get_term_link($term);
			if (is_wp_error($link)) {
				continue;
			}
			$q = new WP_Query(array(
				'post_type' => 'sfwd-courses', 'posts_per_page' => 4, 'no_found_rows' => true, 'ignore_sticky_posts' => true,
				'tax_query' => array(array('taxonomy' => 'ld_course_category', 'field' => 'term_id', 'terms' => (int) $term->term_id)),
			));
			$courses = array();
			foreach ($q->posts as $c) {
				$meta   = get_post_meta($c->ID, '_sfwd-courses', true);
				$ptype  = isset($meta['sfwd-courses_course_price_type']) ? $meta['sfwd-courses_course_price_type'] : '';
				$amount = isset($meta['sfwd-courses_course_price']) ? $meta['sfwd-courses_course_price'] : '';
				$free   = ('free' === $ptype || '' === (string) $amount);
				$author = get_userdata((int) $c->post_author);
				$courses[] = array(
					'id'     => (int) $c->ID,
					'title'  => (string) get_the_title($c),
					'url'    => (string) get_permalink($c),
					'thumb'  => (string) get_the_post_thumbnail_url($c->ID, 'medium'),
					'author' => $author ? (string) $author->display_name : '',
					'price'  => $free ? 'رایگان' : number_format((float) $amount) . ' تومان',
					'free'   => $free,
				);
			}
			wp_reset_postdata();
			if (empty($courses)) {
				continue;
			}
			$tabs[] = array('id' => (int) $term->term_id, 'name' => (string) $term->name, 'url' => (string) $link, 'count' => (int) $term->count, 'courses' => $courses);
		}
	}
	set_transient('evented_nav_course_mega', $tabs, 12 * HOUR_IN_SECONDS);
	return $tabs;
}

/* ---------- ابطال کش: هر تغییری که ساختار منو را عوض کند ---------- */
add_action('created_term', 'evented_nav_flush_cache');
add_action('edited_term', 'evented_nav_flush_cache');
add_action('delete_term', 'evented_nav_flush_cache');
add_action('switch_theme', 'evented_nav_flush_cache');
add_action('update_option_page_for_posts', 'evented_nav_flush_cache');
add_action('update_option_permalink_structure', 'evented_nav_flush_cache');
add_action('activated_plugin', 'evented_nav_flush_cache');
add_action('deactivated_plugin', 'evented_nav_flush_cache');

/* انتشار/حذف برگه ممکن است آدرس بخش‌های ثابت (رمضان، مسابقات، …) را عوض کند. */
add_action('transition_post_status', function ($new_status, $old_status, $post) {
	if (!$post instanceof WP_Post) {
		return;
	}
	if ('page' === $post->post_type && ($new_status !== $old_status)) {
		evented_nav_flush_cache(false);
		return;
	}
	/* انتشار/برداشتن یک نوشته → تب‌های مقالات خانه باید تازه شوند (دسته‌ها ثابت می‌مانند) */
	if ('post' === $post->post_type && ('publish' === $new_status || 'publish' === $old_status)) {
		evented_home_tabs_flush_posts();
	}
}, 10, 3);

/* دکمهٔ «بازسازی منو» در نوار مدیریت (برای وقتی افزونه‌ای پست‌تایپ تازه ثبت می‌کند). */
add_action('admin_bar_menu', function ($bar) {
	if (!current_user_can('manage_options')) {
		return;
	}
	$bar->add_node(array(
		'id'    => 'evented-flush-nav',
		'title' => 'بازسازی منو و کش قالب',
		'href'  => wp_nonce_url(add_query_arg('evented_flush_nav', '1'), 'evented_flush_nav'),
	));
}, 90);

add_action('admin_init', function () {
	if (!isset($_GET['evented_flush_nav']) || !current_user_can('manage_options')) {
		return;
	}
	check_admin_referer('evented_flush_nav');
	evented_nav_flush_cache(true);
	wp_safe_redirect(remove_query_arg(array('evented_flush_nav', '_wpnonce')));
	exit;
});

/* =========================================================================
 * تشخیص آیتم فعال
 * ========================================================================= */

/**
 * کلید آیتم فعال منو بر اساس صفحهٔ جاری.
 *
 * @return string
 */
function evented_nav_current_key()
{
	if (is_front_page()) {
		return 'home';
	}

	if (is_singular('post') || is_home() || is_category() || is_tag() || (is_search() && '' === (string) get_query_var('post_type'))) {
		return 'articles';
	}

	if (is_singular(array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-quizzes'))
		|| is_post_type_archive('sfwd-courses')
		|| is_tax(array('ld_course_category', 'ld_course_tag'))
	) {
		return 'courses';
	}

	$queried_id = is_page() ? (int) get_queried_object_id() : 0;

	foreach (evented_nav_items() as $item) {
		if (!empty($item['post_type']) && 'post' !== $item['post_type']) {
			$pt = $item['post_type'];
			if (is_singular($pt) || is_post_type_archive($pt)) {
				return $item['key'];
			}
			if (is_tax()) {
				$qo  = get_queried_object();
				$tax = ($qo instanceof WP_Term) ? get_taxonomy($qo->taxonomy) : null;
				if ($tax && in_array($pt, (array) $tax->object_type, true)) {
					return $item['key'];
				}
			}
			if (is_search() && (string) get_query_var('post_type') === $pt) {
				return $item['key'];
			}
		}
		if ($queried_id && !empty($item['page_id']) && (int) $item['page_id'] === $queried_id) {
			return $item['key'];
		}
	}

	/* برگه‌های زیرمجموعه (مثلاً /courses/…) */
	if ($queried_id) {
		$ancestors = get_post_ancestors($queried_id);
		foreach (evented_nav_items() as $item) {
			if (!empty($item['page_id']) && in_array((int) $item['page_id'], $ancestors, true)) {
				return $item['key'];
			}
		}
	}

	return '';
}

/* =========================================================================
 * جستجو: دامنه‌های قابل انتخاب در هدر + پاک‌سازی نتایج
 * ========================================================================= */

/**
 * دامنه‌های جستجو برای فیلتر هدر: [post_type => برچسب].
 * کلید خالی = همهٔ بخش‌ها.
 *
 * @return array<string, string>
 */
function evented_search_scopes()
{
	$scopes = array('' => 'همه‌جا');

	foreach (evented_nav_items() as $item) {
		if (empty($item['post_type'])) {
			continue;
		}
		$scopes[$item['post_type']] = $item['title'];
	}

	if (!isset($scopes['post'])) {
		$scopes['post'] = 'مقالات';
	}

	return (array) apply_filters('evented_search_scopes', $scopes);
}

/**
 * پست‌تایپ‌هایی که در جستجوی «همه‌جا» گشته می‌شوند.
 *
 * @return string[]
 */
function evented_search_post_types()
{
	$types = array_values(array_filter(array_keys(evented_search_scopes())));
	$types[] = 'page';
	return array_values(array_unique((array) apply_filters('evented_search_post_types', $types)));
}

/**
 * دامنهٔ جستجوی جاری (از ?post_type=).
 *
 * @return string
 */
function evented_search_current_scope()
{
	$pt = get_query_var('post_type');
	if (is_array($pt)) {
		return '';
	}
	$pt = (string) $pt;
	return array_key_exists($pt, evented_search_scopes()) ? $pt : '';
}

/**
 * فرم جستجوی هدر با فیلتر دامنه.
 *
 * @param string $context 'desktop' یا 'mobile' (برای id یکتا).
 * @return void
 */
function evented_search_form($context = 'desktop')
{
	$scopes  = evented_search_scopes();
	$current = evented_search_current_scope();
	$uid     = 'ee-s-' . sanitize_html_class($context);
	?>
	<form class="ee-search ee-search--<?php echo esc_attr($context); ?>" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr($uid); ?>">جستجو</label>
		<button class="s-ic ee-ic" type="submit" aria-label="جستجو"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg></button>
		<input id="<?php echo esc_attr($uid); ?>" type="search" name="s" placeholder="جستجو در دوره‌ها، مقالات، کتابخانه…" value="<?php echo esc_attr(get_search_query()); ?>" autocomplete="off">
		<span class="ee-search-scope">
			<svg class="ee-ic" focusable="false" aria-hidden="true"><use href="#i-tune"></use></svg>
			<label class="screen-reader-text" for="<?php echo esc_attr($uid); ?>-scope">جستجو در بخش</label>
			<select id="<?php echo esc_attr($uid); ?>-scope" name="post_type" title="محدودهٔ جستجو">
				<?php foreach ($scopes as $value => $label) : ?>
					<option value="<?php echo esc_attr($value); ?>"<?php selected($current, $value); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	</form>
	<?php
}

/**
 * پاک‌سازی کوئری جستجو:
 *  - ?post_type= خالی از URL حذف نشود ولی به‌عنوان «همه» تفسیر شود؛
 *  - در حالت «همه»، فقط بخش‌های سایت (نه درس/آزمون/تاپیک لرن‌دش و فایل‌های رسانه) جستجو شوند.
 */
add_action('pre_get_posts', function ($query) {
	if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
		return;
	}

	$pt = $query->get('post_type');
	if (is_array($pt)) {
		return;
	}
	$pt = (string) $pt;

	if ('' === $pt || 'any' === $pt || !array_key_exists($pt, evented_search_scopes())) {
		$query->set('post_type', evented_search_post_types());
	}
});

/* =========================================================================
 * تب‌های مقالات صفحهٔ اصلی — ۵ دستهٔ تصادفی، کش یک‌هفته‌ای
 * ========================================================================= */

/**
 * پاک کردن فقط بخش «نوشته‌ها»ی کش تب‌ها (دسته‌های انتخاب‌شده تا پایان هفته ثابت می‌مانند).
 *
 * @return void
 */
function evented_home_tabs_flush_posts()
{
	$key  = 'evented_home_tabs_v' . EVENTED_NAV_CACHE_VER;
	$data = get_transient($key);
	if (is_array($data) && isset($data['posts'])) {
		$data['posts'] = array();
		set_transient($key, $data, max(60, (int) ($data['expires'] ?? time()) - time()));
	}
}

/**
 * دادهٔ تب‌های مقالات صفحهٔ اصلی.
 *
 * @param int $tab_count تعداد دسته/تب.
 * @param int $per_tab   تعداد نوشته در هر تب.
 * @return array<int, array{term:WP_Term,posts:WP_Post[]}>
 */
function evented_home_article_tabs($tab_count = 5, $per_tab = 3)
{
	$tab_count = max(1, (int) $tab_count);
	$per_tab   = max(1, (int) $per_tab);
	$key       = 'evented_home_tabs_v' . EVENTED_NAV_CACHE_VER;
	$ttl       = evented_nav_cache_ttl();

	$data = get_transient($key);
	if (!is_array($data) || empty($data['cats']) || (int) ($data['count'] ?? 0) !== $tab_count) {
		$data = array('cats' => array(), 'posts' => array(), 'count' => $tab_count, 'expires' => time() + $ttl);

		$cats = get_categories(array(
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 40, // از بین پرمحتواترین‌ها تصادفی انتخاب می‌کنیم
		));
		$cats = array_values(array_filter((array) $cats, static function ($c) {
			return $c instanceof WP_Term && (int) $c->count > 0 && 'uncategorized' !== $c->slug;
		}));
		shuffle($cats);
		foreach (array_slice($cats, 0, $tab_count) as $c) {
			$data['cats'][] = (int) $c->term_id;
		}
	}

	$dirty = false;
	$out   = array();

	foreach ($data['cats'] as $term_id) {
		$term = get_term((int) $term_id, 'category');
		if (!$term instanceof WP_Term) {
			$dirty = true;
			continue;
		}

		if (!isset($data['posts'][$term_id]) || !is_array($data['posts'][$term_id])) {
			$q = new WP_Query(array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $per_tab,
				'cat'                 => (int) $term_id,
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'orderby'             => 'date',
				'order'               => 'DESC',
			));
			$data['posts'][$term_id] = array_map('intval', (array) $q->posts);
			$dirty = true;
		}

		$posts = array();
		foreach ($data['posts'][$term_id] as $pid) {
			$p = get_post($pid);
			if ($p instanceof WP_Post && 'publish' === $p->post_status) {
				$posts[] = $p;
			}
		}

		if (empty($posts)) {
			continue;
		}

		$out[] = array('term' => $term, 'posts' => $posts);
	}

	if ($dirty) {
		$remaining = max(60, (int) ($data['expires'] ?? (time() + $ttl)) - time());
		set_transient($key, $data, $remaining);
	}

	return $out;
}
