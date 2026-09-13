<?php
/**
 * پارت: سایدبار فیلتر دوره‌ها (بایگانی دوره‌ها و دستهٔ دوره)
 *
 * یک فرم GET با: جستجو، دسته‌ها (لینک)، سطح/هزینه/وضعیت (چیپ رادیویی)،
 * مدرس، مرتب‌سازی. با تغییر هر گزینه خودکار ارسال می‌شود (JS در ee-lms/evented-home).
 *
 * آرگومان‌ها:
 *   ee_current_term — شناسهٔ دستهٔ جاری
 *   ee_total        — تعداد نتایج
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

$ee_current_term = isset($args['ee_current_term']) ? (int) $args['ee_current_term'] : 0;
$ee_total        = isset($args['ee_total']) ? (int) $args['ee_total'] : null;

$ee_defs   = function_exists('evented_course_filter_defs') ? evented_course_filter_defs() : array();
$ee_vals   = function_exists('evented_course_filter_values') ? evented_course_filter_values() : array();
$ee_active = function_exists('evented_course_filters_active') ? evented_course_filters_active() : false;
$ee_base   = function_exists('evented_course_filter_base_url') ? evented_course_filter_base_url() : get_post_type_archive_link('sfwd-courses');
$ee_all    = (string) get_post_type_archive_link('sfwd-courses');
$ee_search = (string) get_search_query();
$ee_fa     = function_exists('evented_fa_digits') ? 'evented_fa_digits' : 'strval';

$ee_cats = get_terms(array('taxonomy' => 'ld_course_category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC'));
if (is_wp_error($ee_cats)) { $ee_cats = array(); }
$ee_total_courses = wp_count_posts('sfwd-courses');
$ee_total_courses = isset($ee_total_courses->publish) ? (int) $ee_total_courses->publish : 0;

/* آدرس دسته با حفظ فیلترهای فعال */
$ee_keep = array();
foreach ($ee_vals as $k => $v) {
	if ('' !== $v && !('orderby' === $k && 'newest' === $v)) { $ee_keep[$k] = $v; }
}
if ('' !== $ee_search) { $ee_keep['s'] = $ee_search; }
$ee_cat_url = static function ($url) use ($ee_keep) { return $ee_keep ? add_query_arg(array_map('rawurlencode', $ee_keep), $url) : $url; };

$ee_active_n = count($ee_keep) - ('' !== $ee_search ? 1 : 0);
$ee_icons = array('level' => 'signal_cellular_alt', 'price' => 'sell', 'status' => 'event_available', 'instructor' => 'person', 'orderby' => 'sort');
?>
<aside class="ee-side ee-fside" data-ee-fside>

	<button type="button" class="ee-fside-toggle" data-ee-fside-toggle aria-expanded="false" aria-controls="eeFsideBody">
		<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-tune"></use></svg>
		<span><?php esc_html_e('فیلتر و مرتب‌سازی', 'evented-edu'); ?></span>
		<?php if ($ee_active_n > 0) : ?><b class="ee-fside-n"><?php echo esc_html($ee_fa($ee_active_n)); ?></b><?php endif; ?>
		<svg class="ee-ic chev" aria-hidden="true" focusable="false"><use href="#i-expand_more"></use></svg>
	</button>

	<div class="ee-fside-body" id="eeFsideBody">
		<form class="ee-fside-form" method="get" action="<?php echo esc_url($ee_base); ?>" data-ee-fside-form>
			<?php if (is_post_type_archive('sfwd-courses') && '' !== $ee_search) : ?><input type="hidden" name="post_type" value="sfwd-courses"><?php endif; ?>

			<!-- جستجو -->
			<div class="ee-fside-box is-search">
				<label class="ee-fside-search">
					<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-search"></use></svg>
					<input type="search" name="s" value="<?php echo esc_attr($ee_search); ?>" placeholder="<?php esc_attr_e('جستجو در دوره‌ها…', 'evented-edu'); ?>" autocomplete="off">
					<?php if ('' !== $ee_search) : ?><a class="ee-fside-clear" href="<?php echo esc_url(remove_query_arg(array('s', 'post_type'))); ?>" aria-label="<?php esc_attr_e('پاک کردن جستجو', 'evented-edu'); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-close"></use></svg></a><?php endif; ?>
				</label>
			</div>

			<!-- خلاصهٔ فعال -->
			<div class="ee-fside-head">
				<span class="ee-fside-title"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-filter_list"></use></svg> <?php esc_html_e('فیلترها', 'evented-edu'); ?><?php if (null !== $ee_total) : ?> <small><?php echo esc_html($ee_fa($ee_total)); ?> <?php esc_html_e('نتیجه', 'evented-edu'); ?></small><?php endif; ?></span>
				<?php if ($ee_active || $ee_current_term) : ?>
					<a class="ee-fside-reset" href="<?php echo esc_url($ee_all); ?>"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-refresh"></use></svg> <?php esc_html_e('پاک کردن همه', 'evented-edu'); ?></a>
				<?php endif; ?>
			</div>

			<!-- دسته‌ها -->
			<?php if (!empty($ee_cats)) : ?>
				<fieldset class="ee-fside-box">
					<legend><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-category"></use></svg> <?php esc_html_e('دسته‌بندی', 'evented-edu'); ?></legend>
					<ul class="ee-fside-cats">
						<li class="<?php echo !$ee_current_term ? 'is-on' : ''; ?>">
							<a href="<?php echo esc_url($ee_cat_url($ee_all)); ?>"><i></i><span><?php esc_html_e('همهٔ دوره‌ها', 'evented-edu'); ?></span><b><?php echo esc_html($ee_fa($ee_total_courses)); ?></b></a>
						</li>
						<?php foreach ($ee_cats as $ee_i => $ee_c) :
							$ee_l = get_term_link($ee_c);
							if (is_wp_error($ee_l)) { continue; }
							?>
							<li class="<?php echo (int) $ee_c->term_id === $ee_current_term ? 'is-on' : ''; ?><?php echo $ee_i >= 7 ? ' is-more' : ''; ?>">
								<a href="<?php echo esc_url($ee_cat_url($ee_l)); ?>"><i></i><span><?php echo esc_html($ee_c->name); ?></span><b><?php echo esc_html($ee_fa((int) $ee_c->count)); ?></b></a>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if (count($ee_cats) > 7) : ?>
						<button type="button" class="ee-fside-more" data-ee-fside-more><span><?php esc_html_e('نمایش همهٔ دسته‌ها', 'evented-edu'); ?></span><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-expand_more"></use></svg></button>
					<?php endif; ?>
				</fieldset>
			<?php endif; ?>

			<!-- چیپ‌های رادیویی: سطح / هزینه / وضعیت -->
			<?php foreach (array('price', 'level', 'status') as $ee_k) :
				if (empty($ee_defs[$ee_k])) { continue; }
				$ee_d = $ee_defs[$ee_k];
				?>
				<fieldset class="ee-fside-box">
					<legend><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-<?php echo esc_attr($ee_icons[$ee_k]); ?>"></use></svg> <?php echo esc_html($ee_d['label']); ?></legend>
					<div class="ee-fside-chips" role="radiogroup" aria-label="<?php echo esc_attr($ee_d['label']); ?>">
						<?php foreach ($ee_d['options'] as $ee_ov => $ee_ol) : ?>
							<label class="ee-fside-chip<?php echo (string) $ee_ov === (string) $ee_vals[$ee_k] ? ' is-on' : ''; ?>">
								<input type="radio" name="<?php echo esc_attr($ee_k); ?>" value="<?php echo esc_attr($ee_ov); ?>"<?php checked((string) $ee_vals[$ee_k], (string) $ee_ov); ?>>
								<span><?php echo esc_html('' === $ee_ov ? __('همه', 'evented-edu') : $ee_ol); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>

			<!-- مدرس + مرتب‌سازی (select) -->
			<?php $ee_lock_instr = !empty($args['ee_lock_instructor']); ?>
			<?php foreach (array('instructor', 'orderby') as $ee_k) :
				if (empty($ee_defs[$ee_k]) || ('instructor' === $ee_k && $ee_lock_instr)) { continue; }
				$ee_d = $ee_defs[$ee_k];
				?>
				<div class="ee-fside-box">
					<label class="ee-fside-select">
						<span class="lbl"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-<?php echo esc_attr($ee_icons[$ee_k]); ?>"></use></svg> <?php echo esc_html($ee_d['label']); ?></span>
						<span class="ctl">
							<select name="<?php echo esc_attr($ee_k); ?>">
								<?php foreach ($ee_d['options'] as $ee_ov => $ee_ol) : ?>
									<option value="<?php echo esc_attr($ee_ov); ?>"<?php selected((string) $ee_vals[$ee_k], (string) $ee_ov); ?>><?php echo esc_html($ee_ol); ?></option>
								<?php endforeach; ?>
							</select>
							<svg class="ee-ic caret" aria-hidden="true" focusable="false"><use href="#i-expand_more"></use></svg>
						</span>
					</label>
				</div>
			<?php endforeach; ?>

			<div class="ee-fside-actions">
				<button type="submit" class="ee-btn ee-btn-primary"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg> <?php esc_html_e('اعمال فیلترها', 'evented-edu'); ?></button>
				<?php if ($ee_active) : ?><a class="ee-btn ee-btn-ghost" href="<?php echo esc_url('' !== $ee_search ? add_query_arg('s', rawurlencode($ee_search), $ee_base) : $ee_base); ?>"><?php esc_html_e('حذف فیلترها', 'evented-edu'); ?></a><?php endif; ?>
			</div>
		</form>

		<!-- کارت راهنما -->
		<div class="ee-fside-help">
			<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-contact_support"></use></svg>
			<div>
				<b><?php esc_html_e('دورهٔ مناسب را پیدا نکردید؟', 'evented-edu'); ?></b>
				<p><?php esc_html_e('با پشتیبانی در تماس باشید تا راهنمایی‌تان کنیم.', 'evented-edu'); ?></p>
				<a href="<?php echo esc_url(function_exists('evented_nav_url') ? evented_nav_url('contact') : home_url('/contact/')); ?>"><?php esc_html_e('ارتباط با ما', 'evented-edu'); ?> <svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-arrow_back"></use></svg></a>
			</div>
		</div>
	</div>
</aside>
