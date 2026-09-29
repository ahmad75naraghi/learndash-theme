<?php
/**
 * ثبت مستقل محتواهای ساخته‌شده با CPT UI.
 *
 * شناسه‌ها عمداً عین شناسه‌های قبلی‌اند؛ بنابراین خاموش‌کردن CPT UI هیچ رکورد،
 * پیوند یا رابطهٔ taxonomy را جابه‌جا نمی‌کند. اگر CPT UI هنوز فعال باشد، قالب
 * فقط associationها را کامل می‌کند و ثبت افزونه را بازنویسی نمی‌کند.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * برچسب‌های استاندارد یک نوع محتوا.
 *
 * @param string $plural   نام جمع.
 * @param string $singular نام مفرد.
 * @return array<string,string>
 */
function evented_content_type_labels($plural, $singular)
{
	return array(
		'name'                     => $plural,
		'singular_name'            => $singular,
		'menu_name'                => $plural,
		'name_admin_bar'           => $singular,
		'add_new'                  => 'افزودن',
		'add_new_item'             => 'افزودن ' . $singular . ' جدید',
		'edit_item'                => 'ویرایش ' . $singular,
		'new_item'                 => $singular . ' جدید',
		'view_item'                => 'مشاهده ' . $singular,
		'view_items'               => 'مشاهده ' . $plural,
		'all_items'                => 'همهٔ ' . $plural,
		'search_items'             => 'جستجوی ' . $plural,
		'not_found'                => 'موردی پیدا نشد.',
		'not_found_in_trash'       => 'موردی در زباله‌دان پیدا نشد.',
		'featured_image'           => 'تصویر شاخص ' . $singular,
		'set_featured_image'       => 'تنظیم تصویر شاخص',
		'remove_featured_image'    => 'حذف تصویر شاخص',
		'use_featured_image'       => 'استفاده به‌عنوان تصویر شاخص',
		'archives'                 => 'آرشیو ' . $plural,
		'insert_into_item'         => 'درج در ' . $singular,
		'uploaded_to_this_item'    => 'بارگذاری‌شده برای ' . $singular,
		'filter_items_list'        => 'فیلتر فهرست ' . $plural,
		'items_list_navigation'    => 'ناوبری فهرست ' . $plural,
		'items_list'               => 'فهرست ' . $plural,
		'item_published'           => $singular . ' منتشر شد.',
		'item_updated'             => $singular . ' به‌روزرسانی شد.',
	);
}

/** ثبت CPTها و taxonomy با همان کلیدهای ذخیره‌شده در پایگاه داده. */
function evented_register_migrated_content_types()
{
	if (!taxonomy_exists('galery_cat')) {
		register_taxonomy('galery_cat', array('gallery'), array(
			'labels' => array(
				'name'          => 'دسته‌های گالری',
				'singular_name' => 'دسته‌بندی گالری',
				'all_items'     => 'همهٔ دسته‌های گالری',
				'edit_item'     => 'ویرایش دستهٔ گالری',
				'add_new_item'  => 'افزودن دستهٔ گالری',
				'search_items'  => 'جستجوی دسته‌های گالری',
			),
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'sort'               => true,
			'query_var'          => true,
			'rewrite'            => array('slug' => 'galery_cat', 'with_front' => true, 'hierarchical' => true),
		));
	}

	$types = array(
		'lib' => array(
			'plural'       => 'کتابخانه',
			'singular'     => 'کتاب',
			'menu_icon'    => 'dashicons-book-alt',
			'supports'     => array('title', 'editor', 'thumbnail'),
			'taxonomies'   => array('wpdmcategory'),
			'has_archive'  => 'lib',
		),
		'clip' => array(
			'plural'       => 'ویدئوها',
			'singular'     => 'ویدئو',
			'menu_icon'    => 'dashicons-video-alt3',
			'supports'     => array('title', 'editor', 'thumbnail', 'comments'),
			'taxonomies'   => array(),
			'has_archive'  => 'clip',
		),
		'gallery' => array(
			'plural'       => 'گالری',
			'singular'     => 'عکس',
			'menu_icon'    => 'dashicons-format-gallery',
			'supports'     => array('title', 'editor', 'thumbnail'),
			'taxonomies'   => array('galery_cat'),
			'has_archive'  => 'gallery',
		),
	);

	foreach ($types as $post_type => $config) {
		if (!post_type_exists($post_type)) {
			register_post_type($post_type, array(
				'label'               => $config['plural'],
				'labels'              => evented_content_type_labels($config['plural'], $config['singular']),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'rest_controller_class' => 'WP_REST_Posts_Controller',
				'rest_namespace'      => 'wp/v2',
				'delete_with_user'    => false,
				'exclude_from_search' => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'can_export'          => false,
				'has_archive'         => $config['has_archive'],
				'rewrite'             => array('slug' => $post_type, 'with_front' => true),
				'query_var'           => true,
				'menu_icon'           => $config['menu_icon'],
				'supports'            => $config['supports'],
				'taxonomies'          => $config['taxonomies'],
			));
		}

		/* وقتی CPT UI فعال است، association ثبت‌شدهٔ همان افزونه حفظ/تکمیل می‌شود. */
		foreach ($config['taxonomies'] as $taxonomy) {
			if (taxonomy_exists($taxonomy)) {
				register_taxonomy_for_object_type($taxonomy, $post_type);
			}
		}
	}
}
add_action('init', 'evented_register_migrated_content_types', 20);

/**
 * ویدئوها taxonomy ندارند؛ association اشتباه نسخه‌های قبلی بدون حذف داده جدا می‌شود.
 *
 * unregister فقط اتصال runtime را برمی‌دارد و هیچ term یا رابطه‌ای را از دیتابیس حذف نمی‌کند.
 */
function evented_detach_clip_taxonomies()
{
	foreach ((array) get_object_taxonomies('clip') as $taxonomy) {
		if (taxonomy_exists($taxonomy) && is_object_in_taxonomy('clip', $taxonomy)) {
			unregister_taxonomy_for_object_type($taxonomy, 'clip');
		}
	}
}
add_action('init', 'evented_detach_clip_taxonomies', 100);

/** بعد از مهاجرت یا تغییر قالب فقط یک بار rewriteها را بازسازی می‌کند. */
function evented_content_types_maybe_flush_rewrite_rules()
{
	$version = '1';
	if ($version === (string) get_option('evented_content_types_rewrite_version', '')) {
		return;
	}
	flush_rewrite_rules(false);
	update_option('evented_content_types_rewrite_version', $version, false);
}
add_action('init', 'evented_content_types_maybe_flush_rewrite_rules', 99);
add_action('deactivated_plugin', static function ($plugin) {
	if (false !== strpos((string) $plugin, 'custom-post-type-ui')) {
		delete_option('evented_content_types_rewrite_version');
	}
});
