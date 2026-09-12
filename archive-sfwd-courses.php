<?php
/**
 * آرشیو همهٔ دوره‌ها (post type archive) — پوستهٔ «evented-edu»
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-courses ee-archive-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'courses'));

global $wp_query;

$ee_posts = isset($wp_query->posts) && is_array($wp_query->posts) ? $wp_query->posts : array();
$ee_total = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : count($ee_posts);

$ee_search = (string) get_search_query();
$ee_title  = '' !== $ee_search
	? sprintf(__('نتایج جستجوی «%s» در دوره‌ها', 'evented-edu'), $ee_search)
	: __('همهٔ دوره‌های آموزشی', 'evented-edu');
?>

<main class="ee-list-main">
	<div class="ee-wrap ee-list-grid">

		<div class="ee-list-col">

			<header class="ee-list-head">
				<div class="ee-lh-txt">
					<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
						<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-chevron_left"></use></svg>
						<span class="ee-crumb-current"><?php echo esc_html($ee_title); ?></span>
					</nav>

					<h1 class="ee-lh-title"><?php echo esc_html($ee_title); ?></h1>

					<span class="ee-lh-count">
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-inventory_2"></use></svg>
						<?php
						/* translators: %s: تعداد دوره */
						echo esc_html(sprintf(_n('%s دوره', '%s دوره', $ee_total, 'evented-edu'), number_format_i18n($ee_total)));
						?>
					</span>
				</div>
			</header>

			<?php
			get_template_part('template-parts/lms/course', 'grid', array(
				'ee_posts' => $ee_posts,
				'ee_empty' => __('دوره‌ای یافت نشد.', 'evented-edu'),
			));
			?>

			<?php echo function_exists('evented_pagination') ? evented_pagination($wp_query) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML امن درون هلپر. ?>

		</div>

		<?php get_template_part('template-parts/lms/courses', 'sidebar'); ?>

	</div>
</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'courses')); ?>
