<?php
/**
 * تولید PDF گواهینامه‌های LearnDash با mPDF.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * Shamiim – گواهینامه PDF (mPDF) روی لینک‌های فرانت‌اند LearnDash
 *
 * اجرا:  /certificates/xxx/?quiz=ID&user=ID&time=TS   (همان لینک‌های فعلی کاربران)
 * کالیبره (فقط مدیر):  ...&debug=grid
 * تغییر زنده مختصات (فقط مدیر): ...&debug=grid&pos[name][y]=92&pos[nid][x]=170
 */


// بستهٔ رسمی قالب vendor/mPDF را همراه خود دارد؛ روی سرور Composer لازم نیست.
$evented_autoload = get_template_directory() . '/vendor/autoload.php';
if (file_exists($evented_autoload)) {
    require_once $evented_autoload;
}

/* -------------------------------------------------------------------------
 * تنظیمات جای متن‌ها  (همه‌چیز به میلی‌متر، از گوشه بالا-چپ صفحه A4 افقی 297×210)
 *  x,y  = گوشه بالا-چپ کادر
 *  w,h  = عرض و ارتفاع کادر
 *  align= left | center | right   (فارسی معمولاً right: لبه راست کادر، تکیه‌گاه متن است)
 *  size = اندازه فونت به pt
 *  overflow = auto (اگر متن بلند بود فونت کوچک می‌شود تا در کادر جا شود) | visible
 * ---------------------------------------------------------------------- */
function shamiim_cert_fields()
{
    $fields = [
        'date'   => ['x' => 40,   'y' => 35,  'w' => 90,  'h' => 10, 'align' => 'left',   'size' => 10.5, 'color' => '#333333', 'bold' => false, 'overflow' => 'visible'],
        'name'   => ['x' => -60, 'y' => 85,  'w' => 150, 'h' => 16, 'align' => 'right', 'size' => 17,   'color' => '#097621', 'bold' => true,  'overflow' => 'auto'],
        'nid'    => ['x' => 172,  'y' => 112, 'w' => 60,  'h' => 11, 'align' => 'right',  'size' => 13,   'color' => '#097621', 'bold' => true,  'overflow' => 'visible'],
        'course' => ['x' => 47,   'y' => 112, 'w' => 90,  'h' => 11, 'align' => 'right',  'size' => 12, 'color' => '#097621', 'bold' => true,  'overflow' => 'auto'],
    ];

    return apply_filters('shamiim_cert_fields', $fields);
}

/* -------------------------------------------------------------------------
 * ابزارها
 * ---------------------------------------------------------------------- */
function shamiim_to_persian_digits($string)
{
    return str_replace(
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
        (string) $string
    );
}

function shamiim_cert_find_attempt($user_id, $quiz_id, $time)
{
    $attempts = get_user_meta($user_id, '_sfwd-quizzes', true);
    if (! is_array($attempts)) {
        return null;
    }

    foreach ($attempts as $attempt) {
        if (
            (int) ($attempt['quiz'] ?? 0) === $quiz_id
            && (int) ($attempt['time'] ?? 0) === $time
            && ! empty($attempt['pass'])
        ) {
            return $attempt;
        }
    }

    return null;
}

/**
 * مسیر فایل بک‌گراند: اول تصویر شاخص پست گواهینامه، بعد فایل پیش‌فرض
 */
function shamiim_cert_background_path($cert_post_id)
{
    if ($cert_post_id) {
        $thumb_id = get_post_thumbnail_id($cert_post_id);
        if ($thumb_id) {
            $file = get_attached_file($thumb_id);
            if ($file && file_exists($file)) {
                return $file;
            }
        }
    }

    $upload = wp_get_upload_dir();
    $file   = trailingslashit($upload['basedir']) . '2022/05/lic2.jpg';

    return file_exists($file) ? $file : '';
}

/**
 * پیکربندی mPDF همراه فونت TTF فارسی واقعی.
 *
 * @return array{0: array<string,mixed>, 1: string}
 */
function shamiim_cert_mpdf_config($temp_dir)
{
    /*
     * فونت PDF باید TTF باشد؛ mPDF فایل WOFF2 رابط سایت را نمی‌خواند.
     * بررسی اندازه مانع ثبت فایل ناقص/صفر بایتی به‌عنوان فونت می‌شود.
     */
    $fonts_dir   = get_template_directory() . '/assets/fonts';
    $regular_ttf = $fonts_dir . '/Vazirmatn-Regular.ttf';
    $bold_ttf    = $fonts_dir . '/Vazirmatn-Bold.ttf';
    $has_vazir   = is_readable($regular_ttf) && filesize($regular_ttf) > 10000;
    $has_bold    = is_readable($bold_ttf) && filesize($bold_ttf) > 10000;
    $cert_font   = $has_vazir ? 'eventedcert' : 'dejavusans';
    $config      = array(
        'mode'             => 'utf-8',
        'format'           => 'A4-L',
        'tempDir'          => $temp_dir,
        'margin_left'      => 0,
        'margin_right'     => 0,
        'margin_top'       => 0,
        'margin_bottom'    => 0,
        'margin_header'    => 0,
        'margin_footer'    => 0,
        'default_font'     => $cert_font,
        'autoScriptToLang' => false,
        'autoLangToFont'   => false,
    );

    if ($has_vazir) {
        $config['fontDir'] = array_merge(
            (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'],
            array($fonts_dir)
        );
        $config['fontdata'] = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + array(
            'eventedcert' => array(
                'R'          => 'Vazirmatn-Regular.ttf',
                'B'          => $has_bold ? 'Vazirmatn-Bold.ttf' : 'Vazirmatn-Regular.ttf',
                'useOTL'     => 0xFF,
                'useKashida' => 75,
            ),
        );
    }

    return array($config, $cert_font);
}

/**
 * قرار دادن یک متن در کادر دقیق (میلی‌متر)
 */
function shamiim_cert_place($mpdf, $text, array $f, $debug = false)
{
    // نام خانواده عمداً اختصاصی است تا cache قدیمی mPDF برای فونت‌های حذف‌شده
    // (Vazir/Shabnam) دوباره استفاده نشود.
    $font  = ! empty($f['font']) && 'eventedcert' === $f['font'] ? 'eventedcert' : 'dejavusans';
    $style = sprintf(
        'direction:rtl; unicode-bidi:embed; font-family:%s; text-align:%s; font-size:%spt; color:%s; font-weight:%s;%s',
        $font,
        $f['align'],
        $f['size'],
        $f['color'],
        ! empty($f['bold']) ? 'bold' : 'normal',
        $debug ? ' border:0.2mm dashed #00aa00;' : ''
    );

    $text = wp_check_invalid_utf8((string) $text, true);
    $html = '<div dir="rtl" lang="fa" style="' . $style . '">' . esc_html($text) . '</div>';

    $mpdf->WriteFixedPosHTML(
        $html,
        (float) $f['x'],
        (float) $f['y'],
        (float) $f['w'],
        (float) $f['h'],
        $f['overflow'] ?? 'visible'
    );
}

/**
 * شبکه میلی‌متری برای کالیبره (با Line خود mPDF)
 */
function shamiim_cert_draw_grid($mpdf)
{
    $mpdf->SetLineWidth(0.15);

    for ($x = 0; $x <= 297; $x += 10) {
        $mpdf->SetDrawColor(255, 0, 0);
        $mpdf->Line($x, 0, $x, 210);
        $mpdf->WriteFixedPosHTML('<div style="font-size:6pt; color:#ff0000;">' . $x . '</div>', $x + 0.5, 0.5, 10, 4, 'visible');
    }

    for ($y = 0; $y <= 210; $y += 10) {
        $mpdf->SetDrawColor(0, 0, 255);
        $mpdf->Line(0, $y, 297, $y);
        $mpdf->WriteFixedPosHTML('<div style="font-size:6pt; color:#0000ff;">' . $y . '</div>', 0.5, $y + 0.5, 10, 4, 'visible');
    }
}

/* -------------------------------------------------------------------------
 * تولید و ارسال PDF
 * ---------------------------------------------------------------------- */
function shamiim_cert_stream_pdf($user_id, $quiz_id, $time, $cert_post_id = 0, $debug = false)
{
    if (! class_exists('\Mpdf\Mpdf')) {
        wp_die('کتابخانه mPDF بارگذاری نشده است.', 'خطا', ['response' => 500]);
    }

    $attempt = shamiim_cert_find_attempt($user_id, $quiz_id, $time);
    if (! $attempt) {
        wp_die('گواهینامه‌ای یافت نشد.', 'خطا', ['response' => 404]);
    }

    /* --- داده‌ها --- */
    $user_info = get_userdata($user_id);
    if (! $user_info) {
        wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
    }

    $student_name = get_user_meta($user_id, 'first_last_name', true);
    if (! $student_name) {
        $student_name = $user_info->display_name ?: trim($user_info->first_name . ' ' . $user_info->last_name);
    }

    $course_id = isset($attempt['course']) ? (int) $attempt['course'] : 0;
    if (! $course_id && function_exists('learndash_get_course_id')) {
        $course_id = (int) learndash_get_course_id($quiz_id);
    }
    $course_title = get_the_title($course_id ?: $quiz_id);

    $national_code = get_user_meta($user_id, 'national_code', true) ?: '---';
    $issue_date    = function_exists('evented_format_jalali')
        ? evented_format_jalali($time, 'Y/m/d')
        : date_i18n('Y/m/d', $time);

    $texts = [
        'date'   => 'تاریخ صدور: ' . shamiim_to_persian_digits($issue_date),
        'name'   => $student_name,
        'nid'    => shamiim_to_persian_digits($national_code),
        'course' => $course_title,
    ];

    /* --- پوشه موقت محافظت‌شده --- */
    $temp_dir = WP_CONTENT_DIR . '/uploads/mpdf-tmp';
    if (! file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
        file_put_contents($temp_dir . '/.htaccess', "Require all denied\nDeny from all\n");
        file_put_contents($temp_dir . '/index.html', '');
    }

    list($mpdf_config, $cert_font) = shamiim_cert_mpdf_config($temp_dir);
    $mpdf = new \Mpdf\Mpdf($mpdf_config);

    $mpdf->SetTitle('گواهینامه - ' . $student_name);
    $mpdf->SetAuthor(get_bloginfo('name'));
    $mpdf->SetDirectionality('rtl');
    $mpdf->SetAutoPageBreak(false);
    $mpdf->AddPage();

    /* --- بک‌گراند تمام‌صفحه (297×210) --- */
    $bg_path = shamiim_cert_background_path($cert_post_id);
    if ($bg_path) {
        $mpdf->Image($bg_path, 0, 0, 297, 210, '', '', true, false);
    }

    /* --- مختصات (با امکان override زنده در حالت debug) --- */
    $fields = shamiim_cert_fields();

    if ($debug && isset($_GET['pos']) && is_array($_GET['pos'])) {
        foreach ($_GET['pos'] as $key => $vals) {
            if (! isset($fields[$key]) || ! is_array($vals)) {
                continue;
            }
            foreach (['x', 'y', 'w', 'h', 'size'] as $k) {
                if (isset($vals[$k]) && is_numeric($vals[$k])) {
                    $fields[$key][$k] = (float) $vals[$k];
                }
            }
            if (isset($vals['align']) && in_array($vals['align'], ['left', 'center', 'right'], true)) {
                $fields[$key]['align'] = $vals['align'];
            }
        }
    }

    foreach ($fields as $key => $f) {
        if (isset($texts[$key])) {
            $f['font'] = $cert_font;
            shamiim_cert_place($mpdf, $texts[$key], $f, $debug);
        }
    }

    if ($debug) {
        shamiim_cert_draw_grid($mpdf);
    }

    /* --- خروجی تمیز --- */
    while (ob_get_level()) {
        ob_end_clean();
    }
    nocache_headers();

    $mpdf->Output('Certificate-' . $quiz_id . '.pdf', \Mpdf\Output\Destination::INLINE);
    exit;
}

/* -------------------------------------------------------------------------
 * هوک روی لینک‌های فعلی گواهینامه LearnDash (بدون wp-admin)
 * ---------------------------------------------------------------------- */
add_action('wp', function () {
    if (! is_singular('sfwd-certificates')) {
        return;
    }

    if (! isset($_GET['quiz'], $_GET['time'])) {
        return;
    }

    if (! is_user_logged_in()) {
        auth_redirect();
    }

    $current_user_id = get_current_user_id();
    $quiz_id         = absint($_GET['quiz']);
    $time            = absint($_GET['time']);
    $req_user_id     = isset($_GET['user']) ? absint($_GET['user']) : $current_user_id;

    if ($req_user_id !== $current_user_id && ! current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز است.', 'خطای دسترسی', ['response' => 403]);
    }

    $debug = isset($_GET['debug']) && 'grid' === $_GET['debug'] && current_user_can('manage_options');

    shamiim_cert_stream_pdf(
        $req_user_id,
        $quiz_id,
        $time,
        (int) get_queried_object_id(),
        $debug
    );
}, 1);
