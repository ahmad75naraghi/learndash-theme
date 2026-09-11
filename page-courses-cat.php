<?php
/**
 * برگهٔ «دسته‌بندی دوره‌ها» — گرید همهٔ دسته‌ها با پوستهٔ «evented-edu»
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-courses ee-page-cat'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'courses'));

while (have_posts()) :
	the_post();

	$ee_terms = get_terms(array(
		'taxonomy'   => 'ld_course_category',
		'hide_empty' => false,
		'orderby'    => 'count',
		'order'      => 'DESC',
	));
	if (is_wp_error($ee_terms)) {
		$ee_terms = array();
	}
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
							<span class="material-symbols-outlined ee-ic">category</span>
							<?php
							/* translators: %s: تعداد دسته */
							echo esc_html(sprintf(_n('%s دسته‌بندی', '%s دسته‌بندی', count($ee_terms), 'evented-edu'), number_format_i18n(count($ee_terms))));
							?>
						</span>
					</div>
				</header>

				<?php
				get_template_part('template-parts/lms/category', 'grid', array('ee_terms' => $ee_terms));
				?>

			</div>

			<?php get_template_part('template-parts/lms/courses', 'sidebar'); ?>

		</div>
	</main>

<?php endwhile; ?>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'courses')); ?>
