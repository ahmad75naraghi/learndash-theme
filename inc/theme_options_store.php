<?php
/**
 * مخزن تنظیمات قالب (evented_theme_options)
 *
 * همهٔ مقادیر قابل‌تنظیم قالب (پیامک، تماس، شبکه‌ها/کانال‌ها، متون صفحهٔ اصلی، سئو، ...)
 * در یک آپشن ذخیره می‌شوند و از طریق `evented_opt('key')` خوانده می‌شوند.
 * ثابت‌های wp-config (مثلاً EVENTED_SMS_PASSWORD) بر مقدار ذخیره‌شده اولویت دارند.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

if (!defined('EVENTED_OPT_KEY')) {
	define('EVENTED_OPT_KEY', 'evented_theme_options');
}

/**
 * تعریف فیلدهای تنظیمات: تب ← فیلدها.
 * type: text|textarea|url|email|number|checkbox|password|select
 *
 * @return array
 */
function evented_options_schema()
{
	static $schema = null;
	if (null !== $schema) {
		return $schema;
	}

	$schema = array(
		'general' => array(
			'title'  => 'عمومی و تماس',
			'icon'   => 'dashicons-admin-site-alt3',
			'fields' => array(
				'site_tagline_fa'   => array('label' => 'شعار کوتاه (زیر لوگو در فوتر)', 'type' => 'textarea', 'default' => 'مرجع تخصصی آموزش‌های آنلاین فناوری اطلاعات؛ شبکه، سرور، امنیت، مجازی‌سازی و CRM با همراهی برترین اساتید کشور.'),
				'license_text'      => array('label' => 'متن مجوز/اعتبار (فوتر)', 'type' => 'text', 'default' => 'دارای مجوز رسمی برگزاری دوره‌های آموزش فناوری'),
				'phone'             => array('label' => 'تلفن پشتیبانی', 'type' => 'text', 'default' => '', 'placeholder' => '021-12345678', 'dir' => 'ltr'),
				'phone_hours'       => array('label' => 'ساعت پاسخگویی', 'type' => 'text', 'default' => 'شنبه تا چهارشنبه ۸:۳۰ الی ۱۷:۳۰'),
				'email'             => array('label' => 'ایمیل پشتیبانی', 'type' => 'email', 'default' => '', 'dir' => 'ltr', 'desc' => 'خالی = ایمیل مدیر سایت'),
				'address'           => array('label' => 'آدرس', 'type' => 'textarea', 'default' => ''),
				'copyright'         => array('label' => 'متن کپی‌رایت', 'type' => 'text', 'default' => '', 'desc' => 'خالی = متن پیش‌فرض با نام سایت'),
				'terms_url'         => array('label' => 'لینک قوانین و مقررات', 'type' => 'url', 'default' => '', 'dir' => 'ltr'),
			),
		),
		'social' => array(
			'title'  => 'کانال‌ها و شبکه‌ها',
			'icon'   => 'dashicons-share',
			'fields' => array(
				'channel_id'  => array('label' => 'شناسهٔ مشترک کانال‌ها (بدون @)', 'type' => 'text', 'default' => '', 'dir' => 'ltr', 'desc' => 'اگر همهٔ کانال‌ها یک شناسه دارند اینجا بنویسید؛ فیلدهای زیر در صورت پر بودن اولویت دارند.'),
				'bale_url'    => array('label' => 'بله', 'type' => 'url', 'default' => '', 'dir' => 'ltr', 'placeholder' => 'https://ble.ir/...'),
				'eitaa_url'   => array('label' => 'ایتا', 'type' => 'url', 'default' => '', 'dir' => 'ltr', 'placeholder' => 'https://eitaa.com/...'),
				'rubika_url'  => array('label' => 'روبیکا', 'type' => 'url', 'default' => '', 'dir' => 'ltr', 'placeholder' => 'https://rubika.ir/...'),
				'soroush_url' => array('label' => 'سروش', 'type' => 'url', 'default' => '', 'dir' => 'ltr', 'placeholder' => 'https://splus.ir/...'),
				'telegram_url'  => array('label' => 'تلگرام', 'type' => 'url', 'default' => '', 'dir' => 'ltr'),
				'instagram_url' => array('label' => 'اینستاگرام', 'type' => 'url', 'default' => '', 'dir' => 'ltr'),
				'youtube_url'   => array('label' => 'یوتیوب / آپارات', 'type' => 'url', 'default' => '', 'dir' => 'ltr'),
				'linkedin_url'  => array('label' => 'لینکدین', 'type' => 'url', 'default' => '', 'dir' => 'ltr'),
			),
		),
		'home' => array(
			'title'  => 'متون صفحهٔ اصلی',
			'icon'   => 'dashicons-admin-home',
			'fields' => array(
				'quick_title'     => array('label' => 'عنوان بخش دسترسی سریع', 'type' => 'text', 'default' => 'دسترسی سریع به بخش‌های سایت'),
				'quick_sub'       => array('label' => 'زیرعنوان دسترسی سریع', 'type' => 'text', 'default' => 'مرجع تخصصی آموزش شبکه، سرور و امنیت'),
				'courses_title'   => array('label' => 'عنوان بخش دوره‌ها', 'type' => 'text', 'default' => 'دوره‌های آموزشی تخصصی'),
				'courses_sub'     => array('label' => 'زیرعنوان دوره‌ها', 'type' => 'text', 'default' => 'آموزش کاربردی فناوری اطلاعات با حضور اساتید و متخصصان تراز اول'),
				'cta_title'       => array('label' => 'عنوان نوار CTA', 'type' => 'text', 'default' => 'ثبت‌نام رسمی، شرکت در آزمون و دریافت مدرک معتبر'),
				'cta_sub'         => array('label' => 'متن نوار CTA', 'type' => 'text', 'default' => 'فعال‌سازی دسترسی به جزوات تخصصی، آزمون‌های دوره و گواهی پایان دوره'),
				'cta_btn'         => array('label' => 'متن دکمهٔ CTA', 'type' => 'text', 'default' => 'شروع یادگیری رایگان'),
				'steps_title'     => array('label' => 'عنوان بخش ۳ گام', 'type' => 'text', 'default' => '۳ گام ساده تا یادگیری مهارت IT'),
				'steps_sub'       => array('label' => 'زیرعنوان ۳ گام', 'type' => 'text', 'default' => 'در چند دقیقه و بدون پیچیدگی، به دوره‌های تخصصی شبکه، سرور و امنیت دسترسی پیدا کنید.'),
				'articles_title'  => array('label' => 'عنوان بخش مقالات', 'type' => 'text', 'default' => 'گزیده مقالات و دانستنی‌های فناوری اطلاعات'),
				'blog_feat_title' => array('label' => 'عنوان بلاگ ویژه', 'type' => 'text', 'default' => 'بلاگ ویژه'),
				'blog_feat_sub'   => array('label' => 'زیرعنوان بلاگ ویژه', 'type' => 'text', 'default' => 'منتخبی از نوشته‌های تیم آموزشی؛ تازه‌ترین تجربه‌ها و راهنماهای کاربردی'),
				'instr_title'     => array('label' => 'عنوان بخش اساتید', 'type' => 'text', 'default' => 'اساتید و متخصصان برجسته'),
				'instr_sub'       => array('label' => 'زیرعنوان اساتید', 'type' => 'text', 'default' => 'همراهی اساتید تراز اول در حوزه شبکه، سرور، امنیت و زیرساخت'),
				'tabs_count'      => array('label' => 'تعداد تب‌های مقالات', 'type' => 'number', 'default' => 5, 'min' => 2, 'max' => 8),
				'tabs_per'        => array('label' => 'تعداد مقاله در هر تب', 'type' => 'number', 'default' => 3, 'min' => 2, 'max' => 6),
			),
		),
		'sms' => array(
			'title'  => 'پیامک و ورود',
			'icon'   => 'dashicons-smartphone',
			'fields' => array(
				'sms_username'  => array('label' => 'نام کاربری پنل پیامک', 'type' => 'text', 'default' => '', 'dir' => 'ltr', 'const' => 'EVENTED_SMS_USERNAME'),
				'sms_password'  => array('label' => 'رمز پنل پیامک', 'type' => 'password', 'default' => '', 'dir' => 'ltr', 'const' => 'EVENTED_SMS_PASSWORD'),
				'sms_body_id'   => array('label' => 'شناسهٔ الگو (bodyId) کد تایید', 'type' => 'text', 'default' => '', 'dir' => 'ltr', 'const' => 'EVENTED_SMS_BODY_ID'),
				'sms_notify_body_id' => array('label' => 'شناسهٔ الگوی اعلان‌ها (اختیاری)', 'type' => 'text', 'default' => '', 'dir' => 'ltr', 'desc' => 'الگوی پیامک برای اعلان‌های عمومی (متن، لینک). خالی = پیامک اعلان ارسال نمی‌شود.'),
				'otp_ttl'       => array('label' => 'اعتبار کد تایید (دقیقه)', 'type' => 'number', 'default' => 10, 'min' => 2, 'max' => 30),
				'otp_rate'      => array('label' => 'فاصلهٔ ارسال مجدد (ثانیه)', 'type' => 'number', 'default' => 60, 'min' => 30, 'max' => 300),
			),
		),
		'seo' => array(
			'title'  => 'سئو',
			'icon'   => 'dashicons-chart-line',
			'fields' => array(
				'seo_enabled'     => array('label' => 'خروجی متا/اسکیما توسط قالب', 'type' => 'checkbox', 'default' => 1, 'desc' => 'اگر Yoast/Rank Math نصب است، خودکار غیرفعال می‌شود.'),
				'seo_home_title'  => array('label' => 'عنوان صفحهٔ اصلی', 'type' => 'text', 'default' => ''),
				'seo_home_desc'   => array('label' => 'توضیحات متای صفحهٔ اصلی', 'type' => 'textarea', 'default' => ''),
				'seo_default_img' => array('label' => 'تصویر پیش‌فرض اشتراک‌گذاری (URL)', 'type' => 'url', 'default' => '', 'dir' => 'ltr'),
				'org_name'        => array('label' => 'نام سازمان (Schema)', 'type' => 'text', 'default' => ''),
				'org_logo'        => array('label' => 'لوگوی سازمان (URL)', 'type' => 'url', 'default' => '', 'dir' => 'ltr', 'desc' => 'خالی = لوگوی سفارشی‌سازی'),
			),
		),
		'notify' => array(
			'title'  => 'اعلان‌ها',
			'icon'   => 'dashicons-bell',
			'fields' => array(
				'notify_enabled'      => array('label' => 'اعلان‌های درون‌سایتی فعال باشد', 'type' => 'checkbox', 'default' => 1),
				'notify_new_lesson'   => array('label' => 'اعلان «درس جدید» به دانشجویان دوره', 'type' => 'checkbox', 'default' => 1),
				'notify_comment'      => array('label' => 'اعلان «پاسخ به دیدگاه»', 'type' => 'checkbox', 'default' => 1),
				'notify_sms'          => array('label' => 'ارسال پیامک برای اعلان‌های مهم', 'type' => 'checkbox', 'default' => 0),
				'notify_keep_days'    => array('label' => 'نگهداری اعلان‌ها (روز)', 'type' => 'number', 'default' => 90, 'min' => 7, 'max' => 365),
			),
		),
	);

	return $schema = (array) apply_filters('evented_options_schema', $schema);
}

/**
 * همهٔ مقادیر پیش‌فرض.
 *
 * @return array
 */
function evented_options_defaults()
{
	$out = array();
	foreach (evented_options_schema() as $tab) {
		foreach ($tab['fields'] as $key => $f) {
			$out[$key] = $f['default'];
		}
	}
	return $out;
}

/**
 * همهٔ تنظیمات (با پیش‌فرض‌ها).
 *
 * @return array
 */
function evented_options()
{
	static $memo = null;
	if (null === $memo) {
		$saved = get_option(EVENTED_OPT_KEY, array());
		$memo  = array_merge(evented_options_defaults(), is_array($saved) ? $saved : array());
	}
	return $memo;
}

/**
 * خواندن یک تنظیم. ثابت wp-config (در صورت تعریف در schema) اولویت دارد.
 *
 * @param string $key
 * @param mixed  $fallback
 * @return mixed
 */
function evented_opt($key, $fallback = null)
{
	foreach (evented_options_schema() as $tab) {
		if (isset($tab['fields'][$key]['const']) && defined($tab['fields'][$key]['const'])) {
			return constant($tab['fields'][$key]['const']);
		}
	}
	$all = evented_options();
	if (array_key_exists($key, $all) && '' !== $all[$key] && null !== $all[$key]) {
		return $all[$key];
	}
	return null !== $fallback ? $fallback : ($all[$key] ?? '');
}

/**
 * پاک کردن memo (بعد از ذخیره).
 */
function evented_options_flush()
{
	// static داخل تابع را نمی‌توان مستقیم ریست کرد؛ با یک فراخوانی جدید در درخواست بعدی تازه می‌شود.
	wp_cache_delete(EVENTED_OPT_KEY, 'options');
}

/**
 * پاک‌سازی و ذخیرهٔ مقادیر ارسالی.
 *
 * @param array $input
 * @return array مقادیر پاک‌شده
 */
function evented_options_sanitize($input)
{
	$clean = array();
	foreach (evented_options_schema() as $tab) {
		foreach ($tab['fields'] as $key => $f) {
			$raw = isset($input[$key]) ? wp_unslash($input[$key]) : null;
			switch ($f['type']) {
				case 'checkbox':
					$clean[$key] = empty($raw) ? 0 : 1;
					break;
				case 'number':
					$n = (int) $raw;
					if (isset($f['min'])) { $n = max((int) $f['min'], $n); }
					if (isset($f['max'])) { $n = min((int) $f['max'], $n); }
					$clean[$key] = $n;
					break;
				case 'url':
					$clean[$key] = null === $raw ? '' : esc_url_raw(trim((string) $raw));
					break;
				case 'email':
					$clean[$key] = null === $raw ? '' : sanitize_email((string) $raw);
					break;
				case 'textarea':
					$clean[$key] = null === $raw ? '' : sanitize_textarea_field((string) $raw);
					break;
				case 'password':
					// اگر خالی فرستاده شد، مقدار قبلی حفظ می‌شود
					$prev = evented_options();
					$clean[$key] = ('' === (string) $raw) ? ($prev[$key] ?? '') : (string) $raw;
					break;
				default:
					$clean[$key] = null === $raw ? '' : sanitize_text_field((string) $raw);
			}
		}
	}
	return $clean;
}

/* ---------- کانال‌ها/شبکه‌ها از تنظیمات ---------- */

/**
 * کانال‌های پیام‌رسان بر اساس تنظیمات (فقط آن‌هایی که آدرس دارند).
 */
add_filter('evented_channel_links', function ($channels) {
	$id  = trim((string) evented_opt('channel_id', ''));
	$map = array(
		'eitaa'   => array('ایتا',   'eitaa_url',   'https://eitaa.com/',  '#f97316'),
		'bale'    => array('بله',    'bale_url',    'https://ble.ir/',     '#3b82f6'),
		'rubika'  => array('روبیکا', 'rubika_url',  'https://rubika.ir/',  '#06b6d4'),
		'soroush' => array('سروش',   'soroush_url', 'https://splus.ir/',   '#10b981'),
		'telegram'=> array('تلگرام', 'telegram_url', '',                   '#0ea5e9'),
	);
	$out = array();
	foreach ($map as $key => $row) {
		list($label, $opt, $base, $color) = $row;
		$url = trim((string) evented_opt($opt, ''));
		if ('' === $url && '' !== $id && '' !== $base) {
			$url = $base . $id;
		}
		if ('' === $url) {
			continue;
		}
		$out[] = array('key' => $key, 'label' => $label, 'url' => $url, 'color' => $color);
	}
	return $out;
}, 5);

add_filter('evented_channel_id', function ($id) {
	$v = trim((string) evented_opt('channel_id', ''));
	return '' !== $v ? $v : $id;
}, 5);

/**
 * شبکه‌های اجتماعی (اینستاگرام، یوتیوب، لینکدین، تلگرام) — برای فوتر.
 *
 * @return array[] {key,label,url,icon}
 */
function evented_social_links()
{
	$map = array(
		'instagram' => array('اینستاگرام', 'instagram_url', 'photo_camera'),
		'youtube'   => array('یوتیوب', 'youtube_url', 'smart_display'),
		'linkedin'  => array('لینکدین', 'linkedin_url', 'work'),
		'telegram'  => array('تلگرام', 'telegram_url', 'send'),
	);
	$out = array();
	foreach ($map as $key => $row) {
		$url = trim((string) evented_opt($row[1], ''));
		if ('' !== $url) {
			$out[] = array('key' => $key, 'label' => $row[0], 'url' => $url, 'icon' => $row[2]);
		}
	}
	return (array) apply_filters('evented_social_links', $out);
}

/**
 * ایمیل پشتیبانی (تنظیم یا ایمیل مدیر).
 */
function evented_support_email()
{
	$e = (string) evented_opt('email', '');
	return is_email($e) ? $e : (string) get_bloginfo('admin_email');
}
