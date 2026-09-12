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
				<?php if (is_search()) : ?>
					<a class="ee-arch-link" href="<?php echo esc_url($ee_blog_url); ?>">
						<?php esc_html_e('همهٔ نوشته‌ها', 'evented-edu'); ?>
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
					</a>
				<?php endif; ?>
			</div>
		</header>

		<?php if (is_search() && !empty($ee_search_scopes)) : ?>
			<!-- چیپ‌های «بخش» جستجو -->
			<nav class="ee-arch-chips" aria-label="<?php esc_attr_e('محدودهٔ جستجو', 'evented-edu'); ?>">
				<?php foreach ($ee_search_scopes as $ee_sc_val => $ee_sc_label) :
					$ee_sc_url = add_query_arg(array('s' => get_search_query(false), 'post_type' => $ee_sc_val), home_url('/'));
					if ('' === $ee_sc_val) { $ee_sc_url = remove_query_arg('post_type', $ee_sc_url); } ?>
					<a class="ee-chip-btn<?php echo $ee_sc_val === $ee_search_scope ? ' ee-on' : ''; ?>" href="<?php echo esc_url($ee_sc_url); ?>"><?php echo esc_html($ee_sc_label); ?></a>
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

			<!-- شبکهٔ کارت نوشته‌ها -->
			<div class="ee-arch-cards">
				<?php
				while (have_posts()) :
					the_post();

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

			<!-- صفحه‌بندی -->
			<?php
			$ee_pager = paginate_links(array(
				'type'      => 'array',
				'prev_text' => '<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_forward"></use></svg>',
				'next_text' => '<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>',
			));
			?>
			<?php if (!empty($ee_pager)) : ?>
				<nav class="ee-pager" aria-label="<?php esc_attr_e('صفحه‌بندی', 'evented-edu'); ?>">
					<?php foreach ($ee_pager as $ee_page_link) : ?>
						<?php echo wp_kses_post($ee_page_link); ?>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

		<?php else : ?>

			<!-- چیزی پیدا نشد -->
			<section class="ee-noresult">
				<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-search_off"></use></svg>
				<h2><?php esc_html_e('موردی یافت نشد', 'evented-edu'); ?></h2>
				<p><?php esc_html_e('با عبارت دیگری جستجو کنید یا از دسته‌بندی‌های بالا استفاده نمایید.', 'evented-edu'); ?></p>
				<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
					<div class="ee-search-inline">
						<label class="screen-reader-text" for="ee-noresult-s"><?php esc_html_e('جستجو', 'evented-edu'); ?></label>
						<input id="ee-noresult-s" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('جستجو…', 'evented-edu'); ?>">
						<?php if ('' !== $ee_search_scope) : ?><input type="hidden" name="post_type" value="<?php echo esc_attr($ee_search_scope); ?>"><?php endif; ?>
						<button type="submit" aria-label="<?php esc_attr_e('جستجو', 'evented-edu'); ?>">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
						</button>
					</div>
				</form>
			</section>

		<?php endif; ?>

	</div>

	<?php get_template_part('template-parts/ee', 'sidebar'); ?>

</div>
