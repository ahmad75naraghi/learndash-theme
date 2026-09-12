<?php
/*
Template Name: صفحه لیست اساتید (Group Leaders)
*/
/**
 * فهرست اساتید (کاربران با نقش group_leader) — پوستهٔ «evented-edu»
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-courses ee-instructors-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'instructors'));

while (have_posts()) :
	the_post();

	$ee_paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
	$ee_list  = function_exists('evented_instructors')
		? evented_instructors(array('per_page' => 8, 'paged' => $ee_paged))
		: array('items' => array(), 'total' => 0, 'pages' => 0, 'current' => 1);
	?>

	<main class="ee-list-main">
		<div class="ee-wrap ee-list-grid">

			<div class="ee-list-col">

				<header class="ee-list-head">
					<div class="ee-lh-txt">
						<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
							<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-chevron_left"></use></svg>
							<span class="ee-crumb-current"><?php the_title(); ?></span>
						</nav>

						<h1 class="ee-lh-title"><?php the_title(); ?></h1>

						<?php if (trim((string) get_the_content()) !== '') : ?>
							<div class="ee-lh-desc"><?php the_content(); ?></div>
						<?php endif; ?>

						<span class="ee-lh-count">
							<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-groups"></use></svg>
							<?php
							/* translators: %s: تعداد مدرس */
							echo esc_html(sprintf(_n('%s مدرس', '%s مدرس', (int) $ee_list['total'], 'evented-edu'), number_format_i18n((int) $ee_list['total'])));
							?>
						</span>
					</div>
				</header>

				<?php if (!empty($ee_list['items'])) : ?>
					<div class="ee-igrid">
						<?php foreach ($ee_list['items'] as $ee_instructor) : ?>
							<article class="ee-icard">
								<a class="ee-icard-top" href="<?php echo esc_url($ee_instructor['url']); ?>">
									<span class="ee-icard-avatar">
										<?php if (!empty($ee_instructor['avatar'])) : ?>
											<img src="<?php echo esc_url($ee_instructor['avatar']); ?>" alt="<?php echo esc_attr($ee_instructor['name']); ?>" loading="lazy" width="88" height="88">
										<?php else : ?>
											<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-person"></use></svg>
										<?php endif; ?>
									</span>
									<span class="ee-icard-txt">
										<span class="ee-icard-name"><?php echo esc_html($ee_instructor['name']); ?></span>
										<span class="ee-icard-meta">
											<?php
											/* translators: %s: تعداد دوره */
											echo esc_html(sprintf(__('%s دوره', 'evented-edu'), number_format_i18n((int) $ee_instructor['course_count'])));
											?>
											<?php if ('' !== $ee_instructor['rating']) : ?>
												<span class="ee-icard-rate"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-star"></use></svg><?php echo esc_html($ee_instructor['rating']); ?></span>
											<?php endif; ?>
										</span>
									</span>
								</a>

								<?php if (!empty($ee_instructor['bio'])) : ?>
									<p class="ee-icard-bio"><?php echo esc_html(wp_trim_words($ee_instructor['bio'], 22, '…')); ?></p>
								<?php endif; ?>

								<div class="ee-icard-foot">
									<span class="ee-icard-students">
										<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-groups"></use></svg>
										<?php echo esc_html(number_format_i18n((int) $ee_instructor['students'])); ?>
										<?php esc_html_e('دانشجو', 'evented-edu'); ?>
									</span>
									<a class="ee-icard-link" href="<?php echo esc_url($ee_instructor['url']); ?>">
										<?php esc_html_e('پروفایل', 'evented-edu'); ?>
										<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg>
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php if ((int) $ee_list['pages'] > 1) : ?>
						<nav class="ee-pager" aria-label="<?php esc_attr_e('صفحه‌بندی اساتید', 'evented-edu'); ?>">
							<?php for ($ee_p = 1; $ee_p <= (int) $ee_list['pages']; $ee_p++) : ?>
								<a class="ee-page-num<?php echo $ee_p === (int) $ee_list['current'] ? ' current' : ''; ?>" href="<?php echo esc_url(add_query_arg('paged', $ee_p, get_permalink())); ?>"><?php echo esc_html(number_format_i18n($ee_p)); ?></a>
							<?php endfor; ?>
						</nav>
					<?php endif; ?>
				<?php else : ?>
					<p class="ee-empty"><?php esc_html_e('مدرسی برای نمایش یافت نشد.', 'evented-edu'); ?></p>
				<?php endif; ?>

			</div>

			<?php get_template_part('template-parts/lms/courses', 'sidebar'); ?>

		</div>
	</main>

<?php endwhile; ?>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'instructors')); ?>
