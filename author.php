<?php
/**
 * پروفایل مدرس (بایگانی نویسنده) — پوستهٔ «evented-edu»
 *
 * کارت مدرس (آواتار، نام، بیو، شبکه‌های اجتماعی)، آمار (دوره‌ها، دانشجویان،
 * امتیاز)، گرید دوره‌های مدرس و فهرست مقالات او.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-courses ee-author-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'instructors'));

global $wp_query;

$ee_author    = get_queried_object();
$ee_author_id = is_object($ee_author) ? (int) $ee_author->ID : 0;

$ee_data = ($ee_author_id && function_exists('evented_instructor_data')) ? evented_instructor_data($ee_author_id) : array();

if (empty($ee_data)) {
	$ee_data = array(
		'id'           => $ee_author_id,
		'name'         => (string) get_the_author_meta('display_name', $ee_author_id),
		'bio'          => (string) get_the_author_meta('description', $ee_author_id),
		'about'        => '',
		'avatar'       => function_exists('get_avatar_url') ? (string) get_avatar_url($ee_author_id, array('size' => 160)) : '',
		'url'          => (string) get_author_posts_url($ee_author_id),
		'course_count' => 0,
		'students'     => 0,
		'rating'       => '',
		'social'       => array(),
	);
}

/* دوره‌های مدرس */
$ee_courses_query = function_exists('evented_courses_query')
	? evented_courses_query(array('author' => $ee_author_id, 'posts_per_page' => 9))
	: new WP_Query(array('post_type' => 'sfwd-courses', 'author' => $ee_author_id, 'posts_per_page' => 9));
$ee_courses = is_array($ee_courses_query->posts) ? $ee_courses_query->posts : array();

/* مقالات مدرس (کوئری اصلی صفحه) */
$ee_articles = isset($wp_query->posts) && is_array($wp_query->posts) ? $wp_query->posts : array();

$ee_social_icons = array(
	'youtube'   => 'smart_display',
	'linkedin'  => 'work',
	'instagram' => 'photo_camera',
	'facebook'  => 'thumb_up',
);
?>

<main class="ee-list-main">
	<div class="ee-wrap ee-list-grid">

		<div class="ee-list-col">

			<!-- کارت مدرس -->
			<header class="ee-instructor-card">
				<span class="ee-ic-avatar">
					<?php if (!empty($ee_data['avatar'])) : ?>
						<img src="<?php echo esc_url($ee_data['avatar']); ?>" alt="<?php echo esc_attr($ee_data['name']); ?>" width="120" height="120">
					<?php else : ?>
						<span class="material-symbols-outlined ee-ic">person</span>
					<?php endif; ?>
				</span>

				<div class="ee-ic-body">
					<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
						<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
						<span class="material-symbols-outlined ee-ic">chevron_left</span>
						<span class="ee-crumb-current"><?php echo esc_html($ee_data['name']); ?></span>
					</nav>

					<h1 class="ee-ic-name"><?php echo esc_html($ee_data['name']); ?></h1>

					<?php if (!empty($ee_data['bio'])) : ?>
						<p class="ee-ic-bio"><?php echo esc_html($ee_data['bio']); ?></p>
					<?php endif; ?>

					<?php
					$ee_has_social = false;
					if (!empty($ee_data['social'])) {
						foreach ($ee_data['social'] as $ee_social_url) {
							if ('' !== trim((string) $ee_social_url)) {
								$ee_has_social = true;
								break;
							}
						}
					}
					if ($ee_has_social) :
						?>
						<div class="ee-ic-social">
							<?php foreach ($ee_data['social'] as $ee_key => $ee_social_url) : ?>
								<?php if ('' === trim((string) $ee_social_url)) : continue; endif; ?>
								<a class="ee-social-dot" href="<?php echo esc_url($ee_social_url); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr($ee_key); ?>">
									<span class="material-symbols-outlined ee-ic"><?php echo esc_html(isset($ee_social_icons[$ee_key]) ? $ee_social_icons[$ee_key] : 'link'); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</header>

			<!-- آمار -->
			<ul class="ee-stats">
				<li>
					<span class="material-symbols-outlined ee-ic">menu_book</span>
					<b><?php echo esc_html(number_format_i18n((int) $ee_data['course_count'])); ?></b>
					<span><?php esc_html_e('دوره', 'evented-edu'); ?></span>
				</li>
				<li>
					<span class="material-symbols-outlined ee-ic">groups</span>
					<b><?php echo esc_html(number_format_i18n((int) $ee_data['students'])); ?></b>
					<span><?php esc_html_e('دانشجو', 'evented-edu'); ?></span>
				</li>
				<li>
					<span class="material-symbols-outlined ee-ic">star</span>
					<b><?php echo esc_html('' !== $ee_data['rating'] ? $ee_data['rating'] : __('—', 'evented-edu')); ?></b>
					<span><?php esc_html_e('امتیاز', 'evented-edu'); ?></span>
				</li>
			</ul>

			<!-- دربارهٔ مدرس -->
			<?php if (!empty($ee_data['about'])) : ?>
				<section class="ee-about">
					<h2 class="ee-w-title"><span class="material-symbols-outlined ee-ic">badge</span> <?php esc_html_e('دربارهٔ مدرس', 'evented-edu'); ?></h2>
					<div class="ee-about-txt"><?php echo wp_kses_post(wpautop($ee_data['about'])); ?></div>
				</section>
			<?php endif; ?>

			<!-- دوره‌ها -->
			<section class="ee-author-courses">
				<h2 class="ee-w-title"><span class="material-symbols-outlined ee-ic">school</span> <?php esc_html_e('دوره‌های این مدرس', 'evented-edu'); ?></h2>
				<?php
				get_template_part('template-parts/lms/course', 'grid', array(
					'ee_posts' => $ee_courses,
					'ee_empty' => __('این مدرس هنوز دوره‌ای منتشر نکرده است.', 'evented-edu'),
				));
				?>
				<?php echo function_exists('evented_pagination') ? evented_pagination($ee_courses_query) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML امن درون هلپر. ?>
			</section>

			<!-- مقالات -->
			<?php if (!empty($ee_articles)) : ?>
				<section class="ee-author-posts">
					<h2 class="ee-w-title"><span class="material-symbols-outlined ee-ic">auto_stories</span> <?php esc_html_e('مقالات این مدرس', 'evented-edu'); ?></h2>
					<div class="ee-mini-list">
						<?php foreach ($ee_articles as $ee_article) : ?>
							<a class="ee-mini" href="<?php echo esc_url(get_permalink($ee_article)); ?>">
								<span class="ee-mini-txt">
									<h4><?php echo esc_html(get_the_title($ee_article)); ?></h4>
									<span class="ee-mini-date"><?php echo esc_html(function_exists('evented_post_date') ? evented_post_date($ee_article) : get_the_date('', $ee_article)); ?></span>
								</span>
								<?php if (has_post_thumbnail($ee_article)) : ?>
									<img class="ee-mini-th" src="<?php echo esc_url(get_the_post_thumbnail_url($ee_article, 'thumbnail')); ?>" alt="<?php echo esc_attr(get_the_title($ee_article)); ?>" loading="lazy">
								<?php else : ?>
									<span class="ee-mini-th ee-mini-noimg ee-ic"><span class="material-symbols-outlined">article</span></span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

		</div>

		<?php get_template_part('template-parts/lms/courses', 'sidebar'); ?>

	</div>
</main>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'instructors')); ?>
