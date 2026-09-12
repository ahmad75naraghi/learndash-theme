<?php
/**
 * آرشیو دستهٔ دوره‌ها (ld_course_category) — پوستهٔ «evented-edu»
 *
 * سربرگ دسته (تصویر، عنوان، توضیح کوتاه، تعداد دوره)، چیپ زیردسته‌ها،
 * گرید کارت دوره‌ها با صفحه‌بندی، سوالات متداول دسته و سایدبار.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-courses ee-tax-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'courses'));

global $wp_query;

$ee_term    = get_queried_object();
$ee_term_id = $ee_term instanceof WP_Term ? (int) $ee_term->term_id : 0;

$ee_title = $ee_term instanceof WP_Term ? (string) $ee_term->name : __('دوره‌های آموزشی', 'evented-edu');
$ee_image = ($ee_term_id && function_exists('evented_term_image')) ? evented_term_image($ee_term_id, 'large') : '';

/* توضیح کوتاه دسته (کلید سفارشی) و در نبود آن توضیح خود دسته */
$ee_desc = $ee_term_id ? (string) get_term_meta($ee_term_id, 'short_discription', true) : '';
if ('' === trim($ee_desc) && $ee_term instanceof WP_Term) {
	$ee_desc = (string) $ee_term->description;
}

$ee_faqs = $ee_term_id ? get_term_meta($ee_term_id, 'ld_category_faqs', true) : array();
$ee_faqs = is_array($ee_faqs) ? $ee_faqs : array();

$ee_children = $ee_term_id ? get_terms(array(
	'taxonomy'   => 'ld_course_category',
	'parent'     => $ee_term_id,
	'hide_empty' => false,
)) : array();
if (is_wp_error($ee_children)) {
	$ee_children = array();
}

$ee_posts  = isset($wp_query->posts) && is_array($wp_query->posts) ? $wp_query->posts : array();
$ee_total  = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : count($ee_posts);
?>

<main class="ee-list-main">
	<div class="ee-wrap ee-list-grid">

		<div class="ee-list-col">

			<!-- سربرگ دسته -->
			<header class="ee-list-head">
				<?php if ('' !== $ee_image) : ?>
					<figure class="ee-lh-media">
						<img src="<?php echo esc_url($ee_image); ?>" alt="<?php echo esc_attr($ee_title); ?>" fetchpriority="high" decoding="async">
					</figure>
				<?php endif; ?>

				<div class="ee-lh-txt">
					<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
						<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-chevron_left"></use></svg>
						<span class="ee-crumb-current"><?php echo esc_html($ee_title); ?></span>
					</nav>

					<h1 class="ee-lh-title"><?php echo esc_html($ee_title); ?></h1>

					<?php if ('' !== trim($ee_desc)) : ?>
						<div class="ee-lh-desc"><?php echo wp_kses_post(wpautop($ee_desc)); ?></div>
					<?php endif; ?>

					<span class="ee-lh-count">
						<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-inventory_2"></use></svg>
						<?php
						/* translators: %s: تعداد دوره */
						echo esc_html(sprintf(_n('%s دوره در این دسته', '%s دوره در این دسته', $ee_total, 'evented-edu'), number_format_i18n($ee_total)));
						?>
					</span>
				</div>
			</header>

			<!-- زیردسته‌ها -->
			<?php if (!empty($ee_children)) : ?>
				<div class="ee-chips">
					<?php foreach ($ee_children as $ee_child) : ?>
						<?php if (!$ee_child instanceof WP_Term) : continue; endif; ?>
						<?php $ee_child_link = get_term_link($ee_child); ?>
						<?php if (is_wp_error($ee_child_link)) : continue; endif; ?>
						<a class="ee-chip-btn" href="<?php echo esc_url($ee_child_link); ?>">
							<?php echo esc_html($ee_child->name); ?>
							<span class="ee-chip-count"><?php echo esc_html(number_format_i18n((int) $ee_child->count)); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- گرید دوره‌ها -->
			<?php
			get_template_part('template-parts/lms/course', 'grid', array(
				'ee_posts' => $ee_posts,
				'ee_empty' => __('در این دسته هنوز دوره‌ای منتشر نشده است.', 'evented-edu'),
			));
			?>

			<?php echo function_exists('evented_pagination') ? evented_pagination($wp_query) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML امن درون هلپر. ?>

			<!-- سوالات متداول دسته -->
			<?php if (!empty($ee_faqs)) : ?>
				<section class="ee-list-faqs">
					<h2 class="ee-w-title"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-help"></use></svg> <?php esc_html_e('سوالات متداول', 'evented-edu'); ?></h2>
					<div class="ee-faqs">
						<?php foreach ($ee_faqs as $ee_faq) : ?>
							<?php if (empty($ee_faq['question'])) : continue; endif; ?>
							<div class="ee-faq">
								<h4><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-help"></use></svg> <?php echo esc_html($ee_faq['question']); ?></h4>
								<p><?php echo esc_html(isset($ee_faq['answer']) ? $ee_faq['answer'] : ''); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

		</div>

		<?php get_template_part('template-parts/lms/courses', 'sidebar', array('ee_current_term' => $ee_term_id)); ?>

	</div>
</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'courses')); ?>
