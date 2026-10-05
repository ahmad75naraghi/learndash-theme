<?php
/**
 * بدنهٔ مشترک فهرست نوشته‌ها (آرشیو، برگهٔ نوشته‌ها و نتایج جستجو)
 *
 * شامل: سربرگ بایگانی + چیپ‌های دسته‌بندی + شبکهٔ کارت نوشته‌ها + صفحه‌بندی.
 * توسط archive.php، home.php و search.php استفاده می‌شود.
 *
 * آرگومان‌های اختیاری (get_template_part):
 *   ee_title    — عنوان دلخواه به‌جای عنوان خودکار.
 *   ee_subtitle — توضیح دلخواه به‌جای توضیح خودکار.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/* ---------- عنوان و توضیح سربرگ ---------- */
$ee_arch_title    = isset($args['ee_title']) ? $args['ee_title'] : '';
$ee_arch_subtitle = isset($args['ee_subtitle']) ? $args['ee_subtitle'] : '';
$ee_arch_taxonomy = isset($args['ee_taxonomy']) ? sanitize_key($args['ee_taxonomy']) : 'category';
$ee_arch_icon     = isset($args['ee_icon']) ? sanitize_key($args['ee_icon']) : 'newspaper';
$ee_item_label    = isset($args['ee_item_label']) ? (string) $args['ee_item_label'] : __('نوشته', 'evented-edu');
$ee_all_url       = isset($args['ee_all_url']) ? (string) $args['ee_all_url'] : '';
$ee_hide_sidebar  = !empty($args['ee_hide_sidebar']);
$ee_category_cards = !empty($args['ee_category_cards']);
$ee_terms_limit   = isset($args['ee_terms_limit']) ? max(0, (int) $args['ee_terms_limit']) : 12;
$ee_meta_on       = static function ($key) {
	return !function_exists('evented_post_meta_visible') || evented_post_meta_visible($key);
};

if ('' === $ee_arch_title) {
	if (is_search()) {
		/* translators: %s: عبارت جستجو */
		$ee_arch_title = sprintf(__('نتایج جستجو برای: %s', 'evented-edu'), get_search_query());
	} elseif (is_home()) {
		$ee_posts_page  = (int) get_option('page_for_posts');
		$ee_arch_title  = $ee_posts_page ? get_the_title($ee_posts_page) : __('مقالات و پژوهش‌ها', 'evented-edu');
	} elseif (is_category() || is_tag()) {
		$ee_arch_title = (string) get_the_archive_title();
	} else {
		$ee_arch_title = (string) wp_get_document_title();
	}
}

if ('' === $ee_arch_subtitle) {
	if (is_category() || is_tag() || is_tax()) {
		$ee_queried     = get_queried_object();
		$ee_arch_subtitle = ($ee_queried instanceof WP_Term && !empty($ee_queried->description))
			? wp_strip_all_tags($ee_queried->description)
			: '';
	} elseif (is_author()) {
		$ee_arch_subtitle = (string) get_the_author_meta('description', (int) get_queried_object_id());
	}
}

/* پیشوند عنوان خودکار وردپرس (مثل «بایگانی دسته: …») حذف می‌شود تا عنوان تمیز باشد */
$ee_arch_title = preg_replace('/^(?:بایگانی|آرشیو|دسته|برچسب|نویسنده)[:：]?\s*/u', '', (string) $ee_arch_title);

$ee_arch_count = isset($GLOBALS['wp_query']->found_posts) ? (int) $GLOBALS['wp_query']->found_posts : 0;

/* ---------- چیپ‌های taxonomy مرتبط با نمای جاری ---------- */
$ee_arch_cats = taxonomy_exists($ee_arch_taxonomy) ? get_terms(array(
	'taxonomy'   => $ee_arch_taxonomy,
	'hide_empty' => in_array($ee_arch_taxonomy, array('category','galery_cat'), true) ? false : !$ee_category_cards,
	'pad_counts' => $ee_category_cards,
	'number'     => in_array($ee_arch_taxonomy, array('category','galery_cat'), true) ? 0 : $ee_terms_limit,
	'orderby'    => 'galery_cat' === $ee_arch_taxonomy ? 'name' : 'count',
	'order'      => 'galery_cat' === $ee_arch_taxonomy ? 'ASC' : 'DESC',
)) : array();
if (is_wp_error($ee_arch_cats)) {
	$ee_arch_cats = array();
} elseif (in_array($ee_arch_taxonomy, array('category','galery_cat'), true) && function_exists('evented_taxonomy_content_totals')) {
	$ee_exact_totals = evented_taxonomy_content_totals($ee_arch_taxonomy);
	$ee_arch_cats = array_values(array_filter($ee_arch_cats, static function ($term) use ($ee_exact_totals) {
		return $term instanceof WP_Term && !empty($ee_exact_totals[(int) $term->term_id]);
	}));
	foreach ($ee_arch_cats as $ee_term) { $ee_term->count = (int) $ee_exact_totals[(int) $ee_term->term_id]; }
	usort($ee_arch_cats, static function ($a, $b) {
		$count_order = (int) $b->count <=> (int) $a->count;
		return 0 !== $count_order ? $count_order : strnatcasecmp($a->name, $b->name);
	});
	if ('category' === $ee_arch_taxonomy && $ee_terms_limit > 0) { $ee_arch_cats = array_slice($ee_arch_cats, 0, $ee_terms_limit); }
}
$ee_queried_term = get_queried_object();
$ee_arch_current = ($ee_queried_term instanceof WP_Term && $ee_arch_taxonomy === $ee_queried_term->taxonomy) ? (int) $ee_queried_term->term_id : 0;

$ee_posts_page_id = (int) get_option('page_for_posts');
$ee_blog_url      = $ee_posts_page_id ? (string) get_permalink($ee_posts_page_id) : (string) home_url('/');
if ('' === $ee_all_url) {
	$ee_all_url = $ee_blog_url;
}

/* در نتایج جستجو: به‌جای دسته‌های وبلاگ، «بخش» جستجو (همه/مقالات/دوره‌ها/…) نمایش داده می‌شود */
$ee_search_scopes = (is_search() && function_exists('evented_search_scopes')) ? evented_search_scopes() : array();
$ee_search_scope  = function_exists('evented_search_current_scope') ? evented_search_current_scope() : '';
if (is_search() && '' !== $ee_search_scope && isset($ee_search_scopes[$ee_search_scope])) {
	/* translators: 1: عبارت جستجو، 2: نام بخش */
	$ee_arch_title = sprintf(__('نتایج جستجوی «%1$s» در %2$s', 'evented-edu'), get_search_query(), $ee_search_scopes[$ee_search_scope]);
}
?>
<div class="ee-wrap ee-arch-grid<?php echo $ee_hide_sidebar ? ' ee-arch-grid-full' : ''; ?>">

	<div class="ee-arch-main">

		<!-- سربرگ بایگانی -->
		<header class="ee-arch-head">
			<h1 class="ee-arch-title">
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-<?php echo esc_attr($ee_arch_icon); ?>"></use></svg>
				<?php echo $ee_arch_title; ?>
			</h1>
			<?php if ('' !== trim((string) $ee_arch_subtitle)) : ?>
				<p class="ee-arch-desc"><?php echo esc_html(wp_trim_words($ee_arch_subtitle, 40)); ?></p>
			<?php endif; ?>
			<div class="ee-arch-meta">
				<span>
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-article"></use></svg>
					<?php
					/* translators: 1: تعداد، 2: نام نوع محتوا */
					echo esc_html(sprintf(__('%1$s %2$s', 'evented-edu'), number_format_i18n($ee_arch_count), $ee_item_label));
					?>
				</span>
				<?php
				/* فیلتر سریع تاریخ */
				$ee_range   = isset($_GET['range']) ? sanitize_key(wp_unslash($_GET['range'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$ee_ranges  = array('' => __('همهٔ زمان‌ها', 'evented-edu'), 'week' => __('این هفته', 'evented-edu'), 'month' => __('این ماه', 'evented-edu'), 'year' => __('امسال', 'evented-edu'));
				?>
				<nav class="ee-arch-range" aria-label="<?php esc_attr_e('فیلتر تاریخ', 'evented-edu'); ?>">
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-calendar_month"></use></svg>
					<?php foreach ($ee_ranges as $ee_rk => $ee_rl) : ?>
						<a class="<?php echo $ee_rk === $ee_range ? 'is-on' : ''; ?>" href="<?php echo esc_url('' === $ee_rk ? remove_query_arg(array('range', 'paged')) : add_query_arg('range', $ee_rk, remove_query_arg('paged'))); ?>"><?php echo esc_html($ee_rl); ?></a>
					<?php endforeach; ?>
				</nav>
			</div>
			<?php if (is_search()) : ?>
				<form class="ee-arch-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
					<label class="screen-reader-text" for="ee-arch-s"><?php esc_html_e('جستجو', 'evented-edu'); ?></label>
					<input id="ee-arch-s" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('عبارت دیگری جست‌وجو کنید…', 'evented-edu'); ?>">
					<?php if ('' !== $ee_search_scope) : ?><input type="hidden" name="post_type" value="<?php echo esc_attr($ee_search_scope); ?>"><?php endif; ?>
					<button type="submit" class="ee-btn ee-btn-primary"><?php esc_html_e('جستجو', 'evented-edu'); ?></button>
				</form>
			<?php endif; ?>
		</header>

		<?php if (is_search() && !empty($ee_search_scopes)) : ?>
			<!-- چیپ‌های «بخش» جستجو -->
			<nav class="ee-arch-chips" aria-label="<?php esc_attr_e('محدودهٔ جستجو', 'evented-edu'); ?>">
				<?php
				$ee_scope_counts = function_exists('evented_search_scope_counts') ? evented_search_scope_counts(get_search_query(false)) : array();
				foreach ($ee_search_scopes as $ee_sc_val => $ee_sc_label) :
					$ee_sc_url = add_query_arg(array('s' => get_search_query(false), 'post_type' => $ee_sc_val), home_url('/'));
					if ('' === $ee_sc_val) { $ee_sc_url = remove_query_arg('post_type', $ee_sc_url); }
					$ee_sc_n = isset($ee_scope_counts[$ee_sc_val]) ? (int) $ee_scope_counts[$ee_sc_val] : -1;
					if ('' !== $ee_sc_val && 0 === $ee_sc_n) { continue; } ?>
					<a class="ee-chip-btn<?php echo $ee_sc_val === $ee_search_scope ? ' ee-on' : ''; ?>" href="<?php echo esc_url($ee_sc_url); ?>"><?php echo esc_html($ee_sc_label); ?><?php if ($ee_sc_n >= 0) : ?><span class="ee-chip-count"><?php echo esc_html(number_format_i18n($ee_sc_n)); ?></span><?php endif; ?></a>
				<?php endforeach; ?>
			</nav>
		<?php elseif ($ee_category_cards && !empty($ee_arch_cats)) :
			$ee_gallery_cover_map = array();
			$ee_gallery_cover_ids = get_posts(array(
				'post_type' => 'gallery', 'post_status' => 'publish', 'posts_per_page' => -1,
				'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true,
				'meta_query' => array(array('key' => '_thumbnail_id', 'compare' => 'EXISTS')),
			));
			foreach ($ee_gallery_cover_ids as $ee_gallery_cover_id) {
				$ee_cover_terms = get_the_terms($ee_gallery_cover_id, $ee_arch_taxonomy);
				if (is_wp_error($ee_cover_terms)) { continue; }
				foreach ((array) $ee_cover_terms as $ee_cover_term) {
					if (!isset($ee_gallery_cover_map[$ee_cover_term->term_id])) { $ee_gallery_cover_map[$ee_cover_term->term_id] = (int) $ee_gallery_cover_id; }
				}
			}
			$ee_gallery_terms_by_id = array();
			$ee_gallery_children    = array();
			foreach ($ee_arch_cats as $ee_gallery_term) {
				if (!$ee_gallery_term instanceof WP_Term || (int) $ee_gallery_term->count < 1) { continue; }
				$term_id = (int) $ee_gallery_term->term_id;
				$ee_gallery_terms_by_id[$term_id] = $ee_gallery_term;
				$ee_gallery_children[(int) $ee_gallery_term->parent][] = $term_id;
				/* تصویر اختصاصی taxonomy مقدم است؛ در نبود آن تصویر یک گالری داخل دسته استفاده می‌شود. */
				foreach (array('thumbnail_id', 'image_id', 'galery_cat_image_id', 'category_image_id') as $image_meta_key) {
					$term_image_id = absint(get_term_meta($term_id, $image_meta_key, true));
					if ($term_image_id && wp_attachment_is_image($term_image_id)) {
						$ee_gallery_cover_map[$term_id] = $term_image_id;
						break;
					}
				}
			}
			$ee_find_gallery_cover = static function ($term_id, $trail = array()) use (&$ee_find_gallery_cover, &$ee_gallery_cover_map, $ee_gallery_children) {
				if (!empty($ee_gallery_cover_map[$term_id])) { return (int) $ee_gallery_cover_map[$term_id]; }
				if (isset($trail[$term_id])) { return 0; }
				$trail[$term_id] = true;
				foreach ((array) ($ee_gallery_children[$term_id] ?? array()) as $child_id) {
					$cover_id = $ee_find_gallery_cover($child_id, $trail);
					if ($cover_id) { $ee_gallery_cover_map[$term_id] = $cover_id; return $cover_id; }
				}
				return 0;
			};
			foreach (array_keys($ee_gallery_terms_by_id) as $gallery_term_id) { $ee_find_gallery_cover($gallery_term_id); }
			$ee_render_gallery_terms = static function ($parent_id, $depth = 0) use (&$ee_render_gallery_terms, $ee_gallery_children, $ee_gallery_terms_by_id, $ee_gallery_cover_map) {
				if (empty($ee_gallery_children[$parent_id])) { return; }
				echo '<div class="ee-gallery-category-grid ee-gallery-category-level ee-gallery-category-depth-' . (int) min(4, $depth) . '">';
				foreach ($ee_gallery_children[$parent_id] as $ee_gallery_term_id) {
					if (empty($ee_gallery_terms_by_id[$ee_gallery_term_id])) { continue; }
					$ee_cat          = $ee_gallery_terms_by_id[$ee_gallery_term_id];
					$ee_has_children = !empty($ee_gallery_children[$ee_gallery_term_id]);
					if ($ee_has_children) {
						echo '<section class="ee-gallery-category-group"><header class="ee-gallery-category-group-head"><span class="ee-gallery-category-group-icon"><svg class="ee-ic" aria-hidden="true"><use href="#i-folder"></use></svg></span><div><h3>' . esc_html($ee_cat->name) . '</h3><p>' . esc_html(sprintf(__('%s تصویر در زیر‌دسته‌ها', 'evented-edu'), number_format_i18n((int) $ee_cat->count))) . '</p></div></header>';
						$ee_render_gallery_terms($ee_gallery_term_id, $depth + 1);
						echo '</section>';
						continue;
					}
					$ee_term_link = get_term_link($ee_cat);
					if (is_wp_error($ee_term_link)) { continue; }
					$ee_cover_id = isset($ee_gallery_cover_map[$ee_cat->term_id]) ? (int) $ee_gallery_cover_map[$ee_cat->term_id] : 0;
					if (!$ee_cover_id) { continue; }
					?>
					<a class="ee-gallery-category-card" href="<?php echo esc_url($ee_term_link); ?>">
						<span class="ee-gallery-category-media">
							<?php if ($ee_cover_id && has_post_thumbnail($ee_cover_id)) : ?><?php echo get_the_post_thumbnail($ee_cover_id, 'medium_large', array('loading' => 'lazy', 'decoding' => 'async', 'alt' => '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php else : ?><span class="ee-gallery-category-placeholder"><svg class="ee-ic" aria-hidden="true"><use href="#i-photo_library"></use></svg></span><?php endif; ?>
							<span class="ee-gallery-category-shade"></span>
						</span>
						<span class="ee-gallery-category-info"><strong><?php echo esc_html($ee_cat->name); ?></strong><span><?php echo esc_html(sprintf(__('%s تصویر', 'evented-edu'), number_format_i18n((int) $ee_cat->count))); ?></span><svg class="ee-ic" aria-hidden="true"><use href="#i-arrow_back"></use></svg></span>
					</a>
					<?php
				}
				echo '</div>';
			};
			?>
			<section class="ee-gallery-categories" aria-labelledby="eeGalleryCategoriesTitle">
				<div class="ee-gallery-categories-head">
					<div>
						<span class="ee-resource-eyebrow"><svg class="ee-ic" aria-hidden="true"><use href="#i-category"></use></svg><?php esc_html_e('دسته‌بندی تصاویر', 'evented-edu'); ?></span>
						<h2 id="eeGalleryCategoriesTitle"><?php esc_html_e('گالری‌ها را بر اساس موضوع ببینید', 'evented-edu'); ?></h2>
					</div>
					<p><?php esc_html_e('دسته‌ها از بیشترین تعداد تصویر به کمترین مرتب شده‌اند.', 'evented-edu'); ?></p>
				</div>
				<?php $ee_render_gallery_terms(0); ?>
			</section>
		<?php elseif (!empty($ee_arch_cats)) : ?>
			<!-- چیپ‌های دسته‌بندی -->
			<nav class="ee-arch-chips" aria-label="<?php esc_attr_e('فیلتر دسته‌بندی', 'evented-edu'); ?>">
				<a class="ee-chip-btn<?php echo 0 === $ee_arch_current ? ' ee-on' : ''; ?>" href="<?php echo esc_url($ee_all_url); ?>">
					<?php esc_html_e('همه', 'evented-edu'); ?>
				</a>
				<?php foreach ($ee_arch_cats as $ee_cat) : ?>
					<a class="ee-chip-btn<?php echo (int) $ee_cat->term_id === $ee_arch_current ? ' ee-on' : ''; ?>" href="<?php echo esc_url(get_term_link($ee_cat)); ?>">
						<?php echo esc_html($ee_cat->name); ?>
						<span class="ee-chip-count"><?php echo esc_html(number_format_i18n((int) $ee_cat->count)); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if (have_posts()) : ?>

			<?php if (is_search() && 'sfwd-courses' === $ee_search_scope && function_exists('evented_catalog_card_html')) : ?>
				<!-- نتایج دوره‌ها با کارت واحد دوره -->
				<?php get_template_part('template-parts/lms/course', 'grid', array('ee_posts' => $GLOBALS['wp_query']->posts)); ?>
			<?php else : ?>
			<!-- شبکهٔ کارت نوشته‌ها -->
			<div class="ee-arch-cards">
				<?php
				while (have_posts()) :
					the_post();
					if ('sfwd-courses' === get_post_type() && function_exists('evented_catalog_card_html')) {
						echo '<div class="ee-cgrid-v2 is-inline">' . evented_catalog_card_html(evented_catalog_card_data(get_the_ID())) . '</div>'; // phpcs:ignore
						continue;
					}

					$ee_id       = get_the_ID();
					$ee_p_cats   = 'category' === $ee_arch_taxonomy ? get_the_category($ee_id) : get_the_terms($ee_id, $ee_arch_taxonomy);
					$ee_p_cats   = is_wp_error($ee_p_cats) ? array() : (array) $ee_p_cats;
					$ee_p_cat    = !empty($ee_p_cats) ? $ee_p_cats[0] : null;
					$ee_p_tag    = $ee_meta_on('category') && $ee_p_cat instanceof WP_Term ? $ee_p_cat->name : '';
					if ('' === $ee_p_tag && 'post' !== get_post_type($ee_id)) {
						$ee_pto   = get_post_type_object(get_post_type($ee_id));
						$ee_p_tag = $ee_pto ? (string) $ee_pto->labels->singular_name : '';
					}
					$ee_p_read   = function_exists('evented_reading_time') ? evented_reading_time($ee_id) : 1;
					$ee_p_date   = function_exists('evented_post_date') ? evented_post_date($ee_id, 'Y/m/d') : get_the_date();
					$ee_p_views  = function_exists('evented_get_post_views') ? evented_get_post_views($ee_id) : 0;
					$ee_p_cmts   = (int) get_comments_number($ee_id);
					$ee_p_author  = (string) get_the_author_meta('display_name', (int) get_post_field('post_author', $ee_id));
					$ee_p_rating  = function_exists('evented_rating_badge_html') ? evented_rating_badge_html($ee_id) : '';
					$ee_has_stats = '' !== $ee_p_rating || $ee_meta_on('author') || $ee_meta_on('reading') || $ee_meta_on('views') || $ee_meta_on('comments');
					?>
					<article id="post-<?php echo esc_attr($ee_id); ?>" <?php post_class('ee-arch-card'); ?>>
						<a class="ee-ac-thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
							<?php if (has_post_thumbnail()) : ?>
								<?php the_post_thumbnail('medium_large', array('loading' => 'lazy')); ?>
							<?php else : ?>
								<span class="ee-ac-noimg ee-ic"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-article"></use></svg></span>
							<?php endif; ?>
							<?php if ('' !== $ee_p_tag) : ?>
								<span class="ee-ac-tag"><?php echo esc_html($ee_p_tag); ?></span>
							<?php endif; ?>
						</a>

						<div class="ee-ac-body">
							<h2 class="ee-ac-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p class="ee-ac-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></p>

							<?php if ($ee_meta_on('date') || $ee_has_stats) : ?>
							<div class="ee-ac-foot">
								<?php if ($ee_meta_on('date')) : ?><span class="ee-ac-date"><?php echo esc_html($ee_p_date); ?></span><?php endif; ?>
								<?php if ($ee_has_stats) : ?>
								<span class="ee-ac-stats">
									<?php echo $ee_p_rating; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php if ($ee_meta_on('author')) : ?><span class="ee-ac-author" title="<?php esc_attr_e('نویسنده', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-person"></use></svg><?php echo esc_html($ee_p_author); ?></span><?php endif; ?>
									<?php if ($ee_meta_on('reading')) : ?><span title="<?php esc_attr_e('زمان مطالعه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg><?php echo esc_html(number_format_i18n($ee_p_read)); ?></span><?php endif; ?>
									<?php if ($ee_meta_on('views')) : ?><span title="<?php esc_attr_e('بازدید', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg><?php echo esc_html(number_format_i18n($ee_p_views)); ?></span><?php endif; ?>
									<?php if ($ee_meta_on('comments')) : ?><span title="<?php esc_attr_e('دیدگاه', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-forum"></use></svg><?php echo esc_html(number_format_i18n($ee_p_cmts)); ?></span><?php endif; ?>
								</span>
								<?php endif; ?>
							</div>
							<?php endif; ?>

							<a class="ee-ac-more" href="<?php the_permalink(); ?>">
								<?php esc_html_e('ادامه مطلب', 'evented-edu'); ?>
								<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
							</a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php endif; ?>

			<!-- صفحه‌بندی -->
			<?php echo function_exists('evented_pagination') ? evented_pagination($GLOBALS['wp_query']) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php else : ?>

			<!-- چیزی پیدا نشد -->
			<?php
			$ee_es_actions = array();
			if (is_search()) {
				if ('' !== $ee_search_scope) { $ee_es_actions[] = array('label' => __('جست‌وجو در همه‌جا', 'evented-edu'), 'url' => add_query_arg('s', get_search_query(false), home_url('/')), 'primary' => true); }
				$ee_es_actions[] = array('label' => __('مشاهدهٔ دوره‌ها', 'evented-edu'), 'url' => function_exists('evented_nav_url') ? evented_nav_url('sfwd-courses') : $ee_blog_url, 'primary' => empty($ee_es_actions));
				$ee_es_actions[] = array('label' => __('همهٔ نوشته‌ها', 'evented-edu'), 'url' => $ee_blog_url);
			} else {
				if ('' !== $ee_range) { $ee_es_actions[] = array('label' => __('همهٔ زمان‌ها', 'evented-edu'), 'url' => remove_query_arg('range'), 'primary' => true); }
				$ee_es_actions[] = array('label' => __('همهٔ نوشته‌ها', 'evented-edu'), 'url' => $ee_blog_url, 'primary' => empty($ee_es_actions));
			}
			if (function_exists('evented_empty_state')) {
				echo evented_empty_state(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'title'   => is_search() ? sprintf(__('برای «%s» چیزی پیدا نشد', 'evented-edu'), get_search_query()) : __('موردی یافت نشد', 'evented-edu'),
					'text'    => is_search() ? __('املای عبارت را بررسی کنید، از کلمات کلی‌تر استفاده کنید یا بخش دیگری را انتخاب کنید.', 'evented-edu') : __('در این بازه یا دسته مطلبی منتشر نشده است.', 'evented-edu'),
					'icon'    => 'search_off',
					'actions' => $ee_es_actions,
				));
			}
			?>

		<?php endif; ?>

	</div>

	<?php if (!$ee_hide_sidebar) { get_template_part('template-parts/ee', 'sidebar'); } ?>

</div>
