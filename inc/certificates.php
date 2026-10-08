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
 * اجرا:  /certificates/xxx/?quiz=ID[&time=TS]   گواهی آزمون (لینک نتیجه، پنل، شورت‌کد)
 *        /certificates/xxx/?course_id=ID    گواهی پایان دوره
 *        (هر دو با user=ID برای مدیر، و cert-nonce که LearnDash می‌فرستد)
 * کالیبره (فقط مدیر):  ...&debug=grid
 * تغییر زنده مختصات (فقط مدیر): ...&debug=grid&pos[name][y]=92&pos[nid][x]=170
 */


// بستهٔ رسمی قالب mPDF را در inc/lib همراه خود دارد؛ روی سرور Composer لازم نیست.
// مسیر vendor قدیمی هم برای نصب‌های قبلی و child-theme پشتیبانی می‌شود.
$evented_autoload_paths = array_unique(array(
    get_stylesheet_directory() . '/inc/lib/autoload.php',
    get_template_directory() . '/inc/lib/autoload.php',
    get_stylesheet_directory() . '/vendor/autoload.php',
    get_template_directory() . '/vendor/autoload.php',
));
foreach ($evented_autoload_paths as $evented_autoload) {
    if (is_readable($evented_autoload)) {
        require_once $evented_autoload;
        if (class_exists('\\Mpdf\\Mpdf')) { break; }
    }
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

/* خروجی HTML قابل چاپ، مستقل از mPDF برای هاست‌هایی که Composer را حذف می‌کنند. */
function shamiim_cert_stream_printable_html(array $texts, $cert_post_id = 0)
{
    $background = '';
    $bg_path = shamiim_cert_background_path($cert_post_id);
    if ($bg_path && is_readable($bg_path)) {
        $type = wp_check_filetype($bg_path);
        $data = file_get_contents($bg_path);
        if (false !== $data) { $background = 'data:' . (! empty($type['type']) ? $type['type'] : 'image/jpeg') . ';base64,' . base64_encode($data); }
    }
    $font_data = '';
    $font_path = get_template_directory() . '/assets/fonts/Vazirmatn-Variable.woff2';
    if (is_readable($font_path)) {
        $data = file_get_contents($font_path);
        if (false !== $data) { $font_data = 'data:font/woff2;base64,' . base64_encode($data); }
    }
    $fields = shamiim_cert_fields();
    nocache_headers(); header('Content-Type: text/html; charset=UTF-8');
    ?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html('گواهینامه - ' . $texts['name']); ?></title><style><?php if ($font_data) : ?>@font-face{font-family:EventedCert;src:url('<?php echo esc_attr($font_data); ?>') format('woff2');font-weight:100 900}<?php endif; ?>@page{size:A4 landscape;margin:0}*{box-sizing:border-box}html,body{margin:0;background:#e5e7eb;font-family:EventedCert,Tahoma,sans-serif}.cert{position:relative;width:297mm;height:210mm;margin:0 auto;background:#fff;overflow:hidden}.cert-bg{position:absolute;inset:0;width:100%;height:100%}.cert-field{position:absolute;direction:rtl;line-height:1.5;white-space:nowrap}.print{position:fixed;z-index:10;left:18px;bottom:18px;border:0;border-radius:10px;background:#087b31;color:#fff;padding:10px 18px;font:700 14px EventedCert,Tahoma;cursor:pointer}@media print{html,body{background:#fff}.print{display:none}.cert{margin:0}}</style></head><body><button class="print" type="button" onclick="window.print()">چاپ / ذخیره PDF</button><main class="cert"><?php if ($background) : ?><img class="cert-bg" src="<?php echo esc_attr($background); ?>" alt=""><?php endif; ?><?php foreach ($fields as $key => $field) : if (! isset($texts[$key])) { continue; } ?><div class="cert-field" style="left:<?php echo (float) $field['x']; ?>mm;top:<?php echo (float) $field['y']; ?>mm;width:<?php echo (float) $field['w']; ?>mm;height:<?php echo (float) $field['h']; ?>mm;text-align:<?php echo esc_attr($field['align']); ?>;font-size:<?php echo (float) $field['size']; ?>pt;color:<?php echo esc_attr($field['color']); ?>;font-weight:<?php echo ! empty($field['bold']) ? '800' : '400'; ?>"><?php echo esc_html(wp_check_invalid_utf8((string) $texts[$key], true)); ?></div><?php endforeach; ?></main></body></html><?php
    exit;
}

/* -------------------------------------------------------------------------
 * داده‌ها: تلاش آزمون یا تکمیل دوره
 * ---------------------------------------------------------------------- */

/**
 * آیا کاربر دوره را تکمیل کرده است؟
 */
function shamiim_cert_course_completed($user_id, $course_id)
{
    if (function_exists('learndash_course_completed')) {
        return (bool) learndash_course_completed($user_id, $course_id);
    }

    return (bool) get_user_meta($user_id, 'course_completed_' . $course_id, true);
}

/**
 * تلاش قبول‌شدهٔ آزمون را پیدا می‌کند.
 *
 * لینک‌های خود LearnDash (نتیجهٔ آزمون، شورت‌کد، پیشخوان دوره) فقط `quiz` و
 * `cert-nonce` دارند و `time` ندارند. برای همین اگر زمان دقیق تلاش در لینک
 * نباشد یا پیدا نشود، آخرین تلاش قبول‌شدهٔ همان آزمون استفاده می‌شود.
 */
function shamiim_cert_resolve_attempt($user_id, $quiz_id, $time = 0)
{
    $attempts = get_user_meta($user_id, '_sfwd-quizzes', true);
    if (! is_array($attempts)) {
        return null;
    }

    $passed = array_values(array_filter($attempts, static function ($attempt) use ($quiz_id) {
        return is_array($attempt)
            && (int) ($attempt['quiz'] ?? 0) === $quiz_id
            && ! empty($attempt['pass']);
    }));

    if (! $passed) {
        return null;
    }

    if ($time) {
        foreach ($passed as $attempt) {
            if ((int) ($attempt['time'] ?? 0) === $time) {
                return $attempt;
            }
        }
    }

    usort($passed, static function (array $a, array $b): int {
        return (int) ($b['time'] ?? 0) <=> (int) ($a['time'] ?? 0);
    });

    return $passed[0];
}

/**
 * متن‌های ثابت گواهی: تاریخ صدور، نام، کد ملی و عنوان دوره.
 */
function shamiim_cert_build_texts($user_id, $course_title, $issue_ts)
{
    $user_info = get_userdata($user_id);
    if (! $user_info) {
        wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
    }

    $student_name = get_user_meta($user_id, 'first_last_name', true);
    if (! $student_name) {
        $student_name = $user_info->display_name ?: trim($user_info->first_name . ' ' . $user_info->last_name);
    }

    $national_code = get_user_meta($user_id, 'national_code', true) ?: '---';
    $issue_date    = function_exists('evented_format_jalali')
        ? evented_format_jalali($issue_ts, 'Y/m/d')
        : date_i18n('Y/m/d', $issue_ts);

    return [
        'date'   => 'تاریخ صدور: ' . shamiim_to_persian_digits($issue_date),
        'name'   => $student_name,
        'nid'    => shamiim_to_persian_digits($national_code),
        'course' => $course_title,
    ];
}

/* -------------------------------------------------------------------------
 * تولید و ارسال PDF
 * ---------------------------------------------------------------------- */

/**
 * گواهی آزمون قبول‌شده.
 */
function shamiim_cert_stream_quiz($user_id, $quiz_id, $time, $cert_post_id = 0, $debug = false)
{
    $attempt = shamiim_cert_resolve_attempt($user_id, $quiz_id, $time);
    if (! $attempt) {
        wp_die('گواهینامه‌ای یافت نشد.', 'خطا', ['response' => 404]);
    }

    $course_id = isset($attempt['course']) ? (int) $attempt['course'] : 0;
    if (! $course_id && function_exists('learndash_get_course_id')) {
        $course_id = (int) learndash_get_course_id($quiz_id);
    }

    $texts = shamiim_cert_build_texts(
        $user_id,
        get_the_title($course_id ?: $quiz_id),
        (int) $attempt['time']
    );

    shamiim_cert_render($texts, 'quiz-' . $quiz_id, $cert_post_id, $debug);
}

/**
 * گواهی پایان دوره (لینک course_id در LearnDash).
 */
function shamiim_cert_stream_course($user_id, $course_id, $cert_post_id = 0, $debug = false)
{
    if (! $course_id || ! shamiim_cert_course_completed($user_id, $course_id)) {
        wp_die('گواهینامه‌ای یافت نشد.', 'خطا', ['response' => 404]);
    }

    $completed_ts = (int) get_user_meta($user_id, 'course_completed_' . $course_id, true);
    $texts        = shamiim_cert_build_texts(
        $user_id,
        get_the_title($course_id),
        $completed_ts > 0 ? $completed_ts : time()
    );

    shamiim_cert_render($texts, 'course-' . $course_id, $cert_post_id, $debug);
}

/**
 * ساخت و ارسال گواهی با mPDF (یا fallback چاپی اگر mPDF نبود).
 */
function shamiim_cert_render(array $texts, $file_id, $cert_post_id = 0, $debug = false)
{
    $student_name = $texts['name'];

    // گواهینامه هیچ‌وقت با خطای «mPDF بارگذاری نشده» متوقف نمی‌شود.
    if (! class_exists('\Mpdf\Mpdf')) {
        shamiim_cert_stream_printable_html($texts, $cert_post_id);
    }

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

    $mpdf->Output('Certificate-' . $file_id . '.pdf', \Mpdf\Output\Destination::INLINE);
    exit;
}

/**
 * از ورودی لینک (URL خام یا تگ <a> که LearnDash برمی‌گرداند) فقط URL را برمی‌گرداند.
 */
function shamiim_cert_extract_url($value)
{
    $value = trim((string) $value);
    if ('' === $value) {
        return '';
    }

    if (0 === strpos($value, '<') && preg_match('/href=["\']([^"\']+)["\']/i', $value, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }

    return $value;
}

/* -------------------------------------------------------------------------
 * هوک روی همهٔ لینک‌های گواهینامهٔ LearnDash (بدون wp-admin)
 *
 * انواع لینک‌ها:
 *   ?quiz=ID[&time=TS][&cert-nonce=…][&user=ID]     گواهی آزمون (نتیجه، پنل، شورت‌کد)
 *   ?course_id=ID[&cert-nonce=…][&user=ID]           گواهی پایان دوره
 *
 * این هوک باید قبل از موتور قدیمی LearnDash (conv_pdf/TCPDF) اجرا شود تا
 * همهٔ مسیرها از همان قالب فارسی mPDF عبور کنند.
 * ---------------------------------------------------------------------- */
add_action('wp', function () {
    if (! is_singular('sfwd-certificates')) {
        return;
    }

    $quiz_id   = isset($_GET['quiz']) ? absint($_GET['quiz']) : 0;
    $course_id = isset($_GET['course_id']) ? absint($_GET['course_id']) : 0;

    if (! $quiz_id && ! $course_id) {
        return;
    }

    if (! is_user_logged_in()) {
        auth_redirect();
    }

    $current_user_id = get_current_user_id();
    $req_user_id     = isset($_GET['user']) ? absint($_GET['user']) : $current_user_id;

    if ($req_user_id !== $current_user_id && ! current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز است.', 'خطای دسترسی', ['response' => 403]);
    }

    $debug        = isset($_GET['debug']) && 'grid' === $_GET['debug'] && current_user_can('manage_options');
    $cert_post_id = (int) get_queried_object_id();

    if ($quiz_id) {
        $time = isset($_GET['time']) ? absint($_GET['time']) : 0;
        shamiim_cert_stream_quiz($req_user_id, $quiz_id, $time, $cert_post_id, $debug);
    }

    shamiim_cert_stream_course($req_user_id, $course_id, $cert_post_id, $debug);
}, 1);
