<?php
/**
 * قالب تکی دوره (LearnDash) — طراحی «evented-edu» مطابق الگوی صفحهٔ دورهٔ شمیم
 *
 * ساختار: کارت سفید دوره (عنوان، بنر، نوار اطلاعات، وضعیت پیشرفت، سرفصل‌ها،
 * آکاردئون‌های معرفی/دستاوردها/سوالات/وبینار، نظرات) + سایدبار (ثبت‌نام، جستجو،
 * اشتراک، سایر دوره‌ها، آخرین دوره‌ها).
 *
 * پرفورمنس: دادهٔ گام‌ها یک‌بار با evented_course_steps() محاسبه و بین
 * قالب/پارت‌ها پاس داده می‌شود؛ برخلاف نسخهٔ پیشین برای هر درس مودال ویدیو
 * ساخته نمی‌شود (حذف N فراخوانی oEmbed و N مودال از DOM).
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

get_template_part('template-parts/ee', 'head', array('ee_body_class' => 'ee-lms ee-course-page'));
get_template_part('template-parts/ee', 'header', array('ee_active' => 'courses'));

while (have_posts()) :
	the_post();

	$ee_course_id = get_the_ID();

	/* ثبت بازدید (مدیران و پیش‌نمایش شمرده نمی‌شوند) */
	if (function_exists('evented_track_post_view')) {
		evented_track_post_view($ee_course_id);
	}

	/* دادهٔ مشترک: یک‌بار محاسبه، چند جا مصرف */
	$ee_steps    = function_exists('evented_course_steps') ? evented_course_steps($ee_course_id) : array();
	$ee_pricing  = function_exists('evented_course_pricing') ? evented_course_pricing($ee_course_id) : array('is_free' => true, 'price_type' => 'open', 'price' => '', 'has_access' => false);
	$ee_progress = function_exists('evented_course_progress') ? evented_course_progress($ee_course_id) : array('percentage' => 0, 'completed' => 0, 'total' => 0);

	$ee_subtitle = (string) get_post_meta($ee_course_id, '_course_subtitle', true);
	$ee_outcomes = (array) get_post_meta($ee_course_id, '_course_outcomes', true);
	$ee_outcomes = array_filter(array_map('strval', $ee_outcomes));
	$ee_faqs     = (array) get_post_meta($ee_course_id, '_course_faq', true);

	$ee_terms     = get_the_terms($ee_course_id, 'ld_course_category');
	$ee_term      = (!is_wp_error($ee_terms) && !empty($ee_terms)) ? $ee_terms[0] : null;
	$ee_tags      = get_the_terms($ee_course_id, 'ld_course_tag');
	$ee_author_id = (int) get_post_field('post_author', $ee_course_id);

	$ee_date     = function_exists('evented_post_date') ? evented_post_date($ee_course_id) : get_the_date();
	$ee_time     = function_exists('evented_post_time') ? evented_post_time($ee_course_id) : get_the_time();
	$ee_views    = function_exists('evented_get_post_views') ? evented_get_post_views($ee_course_id) : 0;
	$ee_comments = (int) get_comments_number($ee_course_id);
	$ee_percent  = isset($ee_progress['percentage']) ? (int) $ee_progress['percentage'] : 0;

	/* وبینار */
	$ee_webinar_active = (string) get_post_meta($ee_course_id, '_webinar_active', true);
	?>

	<main class="ee-lms-main">
		<div class="ee-wrap ee-lms-grid">

			<article id="course-<?php echo esc_attr($ee_course_id); ?>" <?php post_class('ee-course-card'); ?>>

				<!-- مسیریابی -->
				<nav class="ee-crumb" aria-label="<?php esc_attr_e('مسیر صفحه', 'evented-edu'); ?>">
					<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'evented-edu'); ?></a>
					<span class="material-symbols-outlined ee-ic">chevron_left</span>
					<?php if ($ee_term instanceof WP_Term) : ?>
						<a href="<?php echo esc_url(get_term_link($ee_term)); ?>"><?php echo esc_html($ee_term->name); ?></a>
						<span class="material-symbols-outlined ee-ic">chevron_left</span>
					<?php endif; ?>
					<span class="ee-crumb-current"><?php the_title(); ?></span>
				</nav>

				<!-- عنوان -->
				<h1 class="ee-course-title"><?php the_title(); ?></h1>
				<?php if ('' !== trim($ee_subtitle)) : ?>
					<p class="ee-course-subtitle"><?php echo esc_html($ee_subtitle); ?></p>
				<?php endif; ?>

				<!-- بنر دوره -->
				<?php if (has_post_thumbnail()) : ?>
					<figure class="ee-course-hero">
						<?php the_post_thumbnail('large', array('loading' => 'eager', 'fetchpriority' => 'high')); ?>
					</figure>
				<?php endif; ?>

				<!-- نوار اطلاعات -->
				<div class="ee-course-meta">
					<span class="ee-meta-item"><span class="material-symbols-outlined ee-ic">calendar_month</span><?php echo esc_html($ee_date); ?></span>
					<span class="ee-meta-item"><span class="material-symbols-outlined ee-ic">schedule</span><?php echo esc_html($ee_time); ?></span>
					<a class="ee-meta-item" href="#ee-reviews"><span class="material-symbols-outlined ee-ic">forum</span>
						<?php
						if ($ee_comments > 0) {
							/* translators: %s: تعداد دیدگاه */
							echo esc_html(sprintf(_n('%s دیدگاه', '%s دیدگاه', $ee_comments, 'evented-edu'), number_format_i18n($ee_comments)));
						} else {
							esc_html_e('بدون دیدگاه', 'evented-edu');
						}
						?>
					</a>
					<?php if ($ee_term instanceof WP_Term) : ?>
						<a class="ee-meta-item" href="<?php echo esc_url(get_term_link($ee_term)); ?>"><span class="material-symbols-outlined ee-ic">sell</span><?php echo esc_html($ee_term->name); ?></a>
					<?php endif; ?>
					<span class="ee-meta-item ee-meta-views"><span class="material-symbols-outlined ee-ic">visibility</span>
						<?php
						/* translators: %s: تعداد بازدید */
						echo esc_html(sprintf(__('تعداد بازدید : %s', 'evented-edu'), number_format_i18n($ee_views)));
						?>
					</span>
				</div>

				<!-- وضعیت پیشرفت -->
				<div class="ee-course-status">
					<span class="ee-cs-item">
						<span class="material-symbols-outlined ee-ic">update</span>
						<?php
						/* translators: %s: تاریخ آخرین فعالیت */
						echo esc_html(sprintf(__('آخرین به‌روزرسانی: %s', 'evented-edu'), function_exists('evented_post_date') ? evented_post_date($ee_course_id, 'Y/m/d') : get_the_date()));
						?>
					</span>
					<?php if (!empty($ee_pricing['has_access'])) : ?>
						<span class="ee-cs-badge<?php echo $ee_percent >= 100 ? ' is-done' : ''; ?>">
							<?php echo esc_html(sprintf('%s %s٪', $ee_percent >= 100 ? __('تکمیل شده', 'evented-edu') : __('در حال انجام', 'evented-edu'), number_format_i18n($ee_percent))); ?>
						</span>
					<?php else : ?>
						<span class="ee-cs-badge is-new"><?php esc_html_e('ثبت‌نام باز است', 'evented-edu'); ?></span>
					<?php endif; ?>
				</div>

				<!-- سرفصل‌ها -->
				<?php
				get_template_part('template-parts/lms/curriculum', null, array(
					'ee_steps'     => $ee_steps,
					'ee_course_id' => $ee_course_id,
				));
				?>

				<!-- معرفی دوره -->
				<?php if (trim((string) get_the_content()) !== '') : ?>
					<section class="ee-acc is-open" data-acc>
						<button type="button" class="ee-acc-head" aria-expanded="true">
							<span><?php esc_html_e('معرفی دوره', 'evented-edu'); ?></span>
							<span class="material-symbols-outlined ee-ic ee-acc-ic">expand_more</span>
						</button>
						<div class="ee-acc-body">
							<div class="ee-course-body">
								<?php
								the_content();

								if (is_array($ee_tags) && !empty($ee_tags)) :
									?>
									<div class="ee-post-tags">
										<span class="ee-tags-label"><span class="material-symbols-outlined ee-ic">tag</span> <?php esc_html_e('برچسب‌ها:', 'evented-edu'); ?></span>
										<?php foreach ($ee_tags as $ee_tag) : ?>
											<a href="<?php echo esc_url(get_term_link($ee_tag)); ?>"><?php echo esc_html($ee_tag->name); ?></a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</section>
				<?php endif; ?>

				<!-- آنچه می‌آموزید -->
				<?php if (!empty($ee_outcomes)) : ?>
					<section class="ee-acc" data-acc>
						<button type="button" class="ee-acc-head" aria-expanded="false">
							<span><?php esc_html_e('آنچه در این دوره می‌آموزید', 'evented-edu'); ?></span>
							<span class="material-symbols-outlined ee-ic ee-acc-ic">expand_more</span>
						</button>
						<div class="ee-acc-body">
							<ul class="ee-outcomes">
								<?php foreach ($ee_outcomes as $ee_outcome) : ?>
									<li>
										<span class="material-symbols-outlined ee-ic">task_alt</span>
										<?php echo esc_html($ee_outcome); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</section>
				<?php endif; ?>

				<!-- سوالات متداول -->
				<?php if (!empty($ee_faqs)) : ?>
					<section class="ee-acc" data-acc>
						<button type="button" class="ee-acc-head" aria-expanded="false">
							<span><?php esc_html_e('سوالات متداول', 'evented-edu'); ?></span>
							<span class="material-symbols-outlined ee-ic ee-acc-ic">expand_more</span>
						</button>
						<div class="ee-acc-body">
							<div class="ee-faqs">
								<?php foreach ($ee_faqs as $ee_faq) : ?>
									<?php if (empty($ee_faq['question'])) : continue; endif; ?>
									<div class="ee-faq">
										<h4>
											<span class="material-symbols-outlined ee-ic">help</span>
											<?php echo esc_html($ee_faq['question']); ?>
										</h4>
										<p><?php echo esc_html(isset($ee_faq['answer']) ? $ee_faq['answer'] : ''); ?></p>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</section>
				<?php endif; ?>

				<!-- وبینار -->
				<?php if ('yes' === $ee_webinar_active) : ?>
					<?php
					$ee_web_date   = (string) get_post_meta($ee_course_id, '_webinar_date', true);
					$ee_web_time   = (string) get_post_meta($ee_course_id, '_webinar_time', true);
					$ee_web_place  = (string) get_post_meta($ee_course_id, '_webinar_location', true);
					$ee_web_map    = (string) get_post_meta($ee_course_id, '_webinar_map_iframe', true);
					$ee_web_gmap   = (string) get_post_meta($ee_course_id, '_webinar_gmap_link', true);
					$ee_web_neshan = (string) get_post_meta($ee_course_id, '_webinar_neshan_link', true);
					?>
					<section class="ee-acc" data-acc>
						<button type="button" class="ee-acc-head" aria-expanded="false">
							<span><?php esc_html_e('وبینار پیش رو', 'evented-edu'); ?></span>
							<span class="material-symbols-outlined ee-ic ee-acc-ic">expand_more</span>
						</button>
						<div class="ee-acc-body">
							<div class="ee-webinar">
								<ul class="ee-webinar-info">
									<?php if ('' !== $ee_web_date) : ?>
										<li><span class="material-symbols-outlined ee-ic">event</span><span><?php echo esc_html($ee_web_date); ?></span></li>
									<?php endif; ?>
									<?php if ('' !== $ee_web_time) : ?>
										<li><span class="material-symbols-outlined ee-ic">schedule</span><span><?php echo esc_html($ee_web_time); ?></span></li>
									<?php endif; ?>
									<?php if ('' !== $ee_web_place) : ?>
										<li><span class="material-symbols-outlined ee-ic">location_on</span><span><?php echo esc_html($ee_web_place); ?></span></li>
									<?php endif; ?>
								</ul>
								<?php if ('' !== $ee_web_map) : ?>
									<div class="ee-webinar-map">
										<iframe src="<?php echo esc_url($ee_web_map); ?>" loading="lazy" title="<?php esc_attr_e('نقشهٔ محل برگزاری وبینار', 'evented-edu'); ?>" referrerpolicy="no-referrer-when-downgrade"></iframe>
									</div>
								<?php endif; ?>
								<?php if ('' !== $ee_web_gmap || '' !== $ee_web_neshan) : ?>
									<div class="ee-webinar-actions">
										<?php if ('' !== $ee_web_gmap) : ?>
											<a class="ee-btn-ghost" href="<?php echo esc_url($ee_web_gmap); ?>" target="_blank" rel="noopener">
												<span class="material-symbols-outlined ee-ic">directions</span><?php esc_html_e('مسیریابی با گوگل‌مپ', 'evented-edu'); ?>
											</a>
										<?php endif; ?>
										<?php if ('' !== $ee_web_neshan) : ?>
											<a class="ee-btn-ghost" href="<?php echo esc_url($ee_web_neshan); ?>" target="_blank" rel="noopener">
												<span class="material-symbols-outlined ee-ic">map</span><?php esc_html_e('مسیریابی با نشان', 'evented-edu'); ?>
											</a>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</section>
				<?php endif; ?>

				<!-- مدرس دوره -->
				<div class="ee-author-box">
					<span class="ee-ab-av"><?php echo get_avatar($ee_author_id, 72); ?></span>
					<div class="ee-ab-txt">
						<h3><?php echo esc_html(get_the_author_meta('display_name', $ee_author_id)); ?></h3>
						<p><?php echo esc_html(get_the_author_meta('description', $ee_author_id) ? get_the_author_meta('description', $ee_author_id) : __('مدرس دوره‌های evented-edu', 'evented-edu')); ?></p>
						<a class="ee-ab-link" href="<?php echo esc_url(get_author_posts_url($ee_author_id)); ?>">
							<?php esc_html_e('مشاهدهٔ سایر دوره‌ها', 'evented-edu'); ?>
							<span class="material-symbols-outlined ee-ic">arrow_back</span>
						</a>
					</div>
				</div>

				<!-- نظرات و امتیاز -->
				<section class="ee-reviews" id="ee-reviews">
					<h3 class="ee-w-title">
						<span class="material-symbols-outlined ee-ic">rate_review</span>
						<?php esc_html_e('نظرات کاربران', 'evented-edu'); ?>
					</h3>

					<?php
					$ee_review_list = get_comments(array(
						'post_id' => $ee_course_id,
						'status'  => 'approve',
						'number'  => 10,
					));
					?>

					<?php if (!empty($ee_review_list)) : ?>
						<ol class="ee-comment-list">
							<?php foreach ($ee_review_list as $ee_comment) : ?>
								<?php $ee_rating = (int) get_comment_meta($ee_comment->comment_ID, 'review_rating', true); ?>
								<li class="comment">
									<div class="comment-body">
										<div class="comment-author">
											<?php echo get_avatar($ee_comment, 44); ?>
											<cite><?php echo esc_html($ee_comment->comment_author); ?></cite>
										</div>
										<div class="comment-meta">
											<?php echo esc_html(mysql2date('Y/m/d', $ee_comment->comment_date)); ?>
											<?php if ($ee_rating > 0) : ?>
												<span class="ee-stars" role="img" aria-label="<?php echo esc_attr(sprintf('%d از ۵', $ee_rating)); ?>">
													<?php for ($ee_i = 1; $ee_i <= 5; $ee_i++) : ?>
														<span class="material-symbols-outlined ee-ic<?php echo $ee_i <= $ee_rating ? ' is-on' : ''; ?>">star</span>
													<?php endfor; ?>
												</span>
											<?php endif; ?>
										</div>
										<div class="comment-content"><p><?php echo esc_html($ee_comment->comment_content); ?></p></div>
									</div>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php else : ?>
						<p class="ee-empty-inline"><?php esc_html_e('هنوز نظری ثبت نشده است؛ اولین نفر باشید.', 'evented-edu'); ?></p>
					<?php endif; ?>

					<?php if (is_user_logged_in()) : ?>
						<form class="ee-review-form" data-action="submit_course_review" data-course="<?php echo esc_attr($ee_course_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('course_review_nonce')); ?>">
							<div class="ee-review-stars" role="radiogroup" aria-label="<?php esc_attr_e('امتیاز شما', 'evented-edu'); ?>">
								<span class="ee-review-label"><?php esc_html_e('امتیاز شما:', 'evented-edu'); ?></span>
								<?php for ($ee_i = 5; $ee_i >= 1; $ee_i--) : ?>
									<button type="button" class="ee-star-btn" data-value="<?php echo esc_attr($ee_i); ?>" aria-label="<?php echo esc_attr($ee_i); ?>">
										<span class="material-symbols-outlined ee-ic">star</span>
									</button>
								<?php endfor; ?>
								<input type="hidden" name="rating" value="0">
							</div>
							<label class="screen-reader-text" for="ee-review-text"><?php esc_html_e('متن دیدگاه', 'evented-edu'); ?></label>
							<textarea id="ee-review-text" name="content" rows="5" placeholder="<?php esc_attr_e('دیدگاه خود را دربارهٔ این دوره بنویسید...', 'evented-edu'); ?>" required></textarea>
							<div class="ee-review-actions">
								<button type="submit" class="ee-comment-submit">
									<?php esc_html_e('فرستادن دیدگاه', 'evented-edu'); ?>
									<span class="material-symbols-outlined ee-ic">send</span>
								</button>
								<span class="ee-review-msg" role="status" aria-live="polite"></span>
							</div>
						</form>
					<?php else : ?>
						<p class="ee-review-login">
							<?php esc_html_e('برای ثبت دیدگاه ابتدا وارد حساب کاربری شوید.', 'evented-edu'); ?>
							<a href="<?php echo esc_url(home_url('/login')); ?>"><?php esc_html_e('ورود / ثبت‌نام', 'evented-edu'); ?></a>
						</p>
					<?php endif; ?>
				</section>

			</article>

			<?php
			get_template_part('template-parts/lms/course', 'sidebar', array(
				'ee_course_id' => $ee_course_id,
				'ee_steps'     => $ee_steps,
				'ee_pricing'   => $ee_pricing,
				'ee_progress'  => $ee_progress,
			));
			?>

		</div>
	</main>

	<!-- نوار خرید موبایل -->
	<?php if (empty($ee_pricing['has_access'])) : ?>
		<div class="ee-buybar">
			<span class="ee-buybar-price">
				<?php if (!empty($ee_pricing['is_free'])) : ?>
					<?php esc_html_e('رایگان', 'evented-edu'); ?>
				<?php else : ?>
					<?php echo esc_html(number_format_i18n((float) $ee_pricing['price'])); ?>
					<small><?php esc_html_e('تومان', 'evented-edu'); ?></small>
				<?php endif; ?>
			</span>
			<a class="ee-buybar-btn" href="#ee-enroll"><?php esc_html_e('ثبت‌نام در دوره', 'evented-edu'); ?></a>
		</div>
	<?php endif; ?>

<?php endwhile; ?>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'courses')); ?>
