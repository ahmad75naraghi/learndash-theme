<?php
/**
 * برگهٔ «دوره‌ها» (اسلاگ courses) — فهرست همهٔ دوره‌ها با پوستهٔ «evented-edu»
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-courses ee-page-courses'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'courses'));

while (have_posts()) :
	the_post();

	$ee_query = function_exists('evented_courses_query')
		? evented_courses_query(array('posts_per_page' => 12))
		: new WP_Query(array('post_type' => 'sfwd-courses', 'posts_per_page' => 12));

	$ee_posts = is_array($ee_query->posts) ? $ee_query->posts : array();
	$ee_total = (int) $ee_query->found_posts;
	?>

	<main class="ee-list-main">
		<div class="ee-wrap ee-list-grid">

			<div class="ee-list-col">

				<header class="ee-list-head">
					<div class="ee-lh-txt">
						<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
							<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
							<span class="material-symbols-outlined ee-ic">chevron_left</span>
							<span class="ee-crumb-current"><?php the_title(); ?></span>
						</nav>

						<h1 class="ee-lh-title"><?php the_title(); ?></h1>

						<?php if (trim((string) get_the_content()) !== '') : ?>
							<div class="ee-lh-desc"><?php the_content(); ?></div>
						<?php endif; ?>

						<span class="ee-lh-count">
							<span class="material-symbols-outlined ee-ic">inventory_2</span>
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
					'ee_empty' => __('هنوز دوره‌ای منتشر نشده است.', 'evented-edu'),
				));
				?>

				<?php echo function_exists('evented_pagination') ? evented_pagination($ee_query) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML امن درون هلپر. ?>

			</div>

			<?php get_template_part('template-parts/lms/courses', 'sidebar'); ?>

		</div>
	</main>

	<?php wp_reset_postdata(); ?>

<?php endwhile; ?>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'courses')); ?>
