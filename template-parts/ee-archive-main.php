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
	if (is_category() || is_tag()) {
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

/* ---------- چیپ‌های دسته‌بندی ---------- */
$ee_arch_cats = get_categories(array(
	'hide_empty' => true,
	'number'     => 12,
	'orderby'    => 'count',
	'order'      => 'DESC',
));
$ee_arch_current = (is_category() && get_queried_object() instanceof WP_Term) ? (int) get_queried_object_id() : 0;

$ee_posts_page_id = (int) get_option('page_for_posts');
$ee_blog_url      = $ee_posts_page_id ? (string) get_permalink($ee_posts_page_id) : (string) home_url('/');

/* در نتایج جستجو: به‌جای دسته‌های وبلاگ، «بخش» جستجو (همه/مقالات/دوره‌ها/…) نمایش داده می‌شود */
$ee_search_scopes = (is_search() && function_exists('evented_search_scopes')) ? evented_search_scopes() : array();
$ee_search_scope  = function_exists('evented_search_current_scope') ? evented_search_current_scope() : '';
if (is_search() && '' !== $ee_search_scope && isset($ee_search_scopes[$ee_search_scope])) {
	/* translators: 1: عبارت جستجو، 2: نام بخش */
	$ee_arch_title = sprintf(__('نتایج جستجوی «%1$s» در %2$s', 'evented-edu'), get_search_query(), $ee_search_scopes[$ee_search_scope]);
}
?>
<div class="ee-wrap ee-arch-grid">

	<div class="ee-arch-main">

		<!-- سربرگ بایگانی -->
		<header class="ee-arch-head">
			<h1 class="ee-arch-title">
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-newspaper"></use></svg>
				<?php echo esc_html($ee_arch_title); ?>
			</h1>
			<?php if ('' !== trim((string) $ee_arch_subtitle)) : ?>
				<p class="ee-arch-desc"><?php echo esc_html(wp_trim_words($ee_arch_subtitle, 40)); ?></p>
			<?php endif; ?>
			<div class="ee-arch-meta">
				<span>
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-article"></use></svg>
					<?php
					/* translators: %s: تعداد نوشته */
					echo esc_html(sprintf(_n('%s نوشته', '%s نوشته', $ee_arch_count, 'evented-edu'), number_format_i18n($ee_arch_count)));
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
		<?php elseif (!empty($ee_arch_cats)) : ?>
			<!-- چیپ‌های دسته‌بندی -->
			<nav class="ee-arch-chips" aria-label="<?php esc_attr_e('فیلتر دسته‌بندی', 'evented-edu'); ?>">
				<a class="ee-chip-btn<?php echo 0 === $ee_arch_current ? ' ee-on' : ''; ?>" href="<?php echo esc_url($ee_blog_url); ?>">
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
					$ee_p_cats   = get_the_category($ee_id);
					$ee_p_cat    = !empty($ee_p_cats) ? $ee_p_cats[0] : null;
					$ee_p_tag    = $ee_p_cat instanceof WP_Term ? $ee_p_cat->name : '';
					if ('' === $ee_p_tag && 'post' !== get_post_type($ee_id)) {
						$ee_pto   = get_post_type_object(get_post_type($ee_id));
						$ee_p_tag = $ee_pto ? (string) $ee_pto->labels->singular_name : '';
					}
					$ee_p_read   = function_exists('evented_reading_time') ? evented_reading_time($ee_id) : 1;
					$ee_p_date   = function_exists('evented_post_date') ? evented_post_date($ee_id, 'Y/m/d') : get_the_date();
					$ee_p_views  = function_exists('evented_get_post_views') ? evented_get_post_views($ee_id) : 0;
					$ee_p_cmts   = (int) get_comments_number($ee_id);
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

							<div class="ee-ac-foot">
								<span class="ee-ac-date"><?php echo esc_html($ee_p_date); ?></span>
								<span class="ee-ac-stats">
									<?php echo function_exists('evented_rating_badge_html') ? evented_rating_badge_html($ee_id) : ''; // phpcs:ignore ?>
									<span title="<?php esc_attr_e('زمان مطالعه', 'evented-edu'); ?>">
										<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-schedule"></use></svg>
										<?php echo esc_html(number_format_i18n($ee_p_read)); ?>
									</span>
									<span title="<?php esc_attr_e('بازدید', 'evented-edu'); ?>">
										<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-visibility"></use></svg>
										<?php echo esc_html(number_format_i18n($ee_p_views)); ?>
									</span>
									<span title="<?php esc_attr_e('دیدگاه', 'evented-edu'); ?>">
										<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-forum"></use></svg>
										<?php echo esc_html(number_format_i18n($ee_p_cmts)); ?>
									</span>
								</span>
							</div>

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

	<?php get_template_part('template-parts/ee', 'sidebar'); ?>

</div>
