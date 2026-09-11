<?php
/**
 * تنظیمات قالب (evented-edu)
 *
 * صفحهٔ «تنظیمات قالب» در پیشخوان (ظاهر ← تنظیمات قالب):
 * مدیریت اسلایدرهای صفحهٔ اصلی — هر اسلاید شامل:
 *   تصویر (از کتابخانهٔ رسانه وردپرس) + عنوان + توضیحات + لینک
 *
 * داده‌ها در آپشن `evented_home_slides` ذخیره می‌شوند و front-page.php
 * آن‌ها را در بخش هیرو رندر می‌کند (در نبود اسلاید، بنر پیش‌فرض قبلی نمایش داده می‌شود).
 */

defined('ABSPATH') || exit;

if (!defined('EVENTED_OPT_SLIDES')) {
    define('EVENTED_OPT_SLIDES', 'evented_home_slides');
}

/* ---------- منوی پیشخوان ---------- */
add_action('admin_menu', function () {
    add_theme_page(
        'تنظیمات قالب',
        'تنظیمات قالب',
        'manage_options',
        'evented-theme-settings',
        'evented_render_theme_settings_page'
    );
});

/**
 * آیا در صفحهٔ «تنظیمات قالب» هستیم؟
 *
 * علاوه بر هوک استاندارد (`appearance_page_evented-theme-settings`)، slug صفحه هم
 * بررسی می‌شود: اگر افزونه‌ای منو را جابه‌جا کند یا هوک پیشخوان تغییر کند،
 * کتابخانهٔ رسانه و اسکریپت اسلایدرها همچنان بارگذاری می‌شوند و دکمهٔ
 * «انتخاب تصویر» بی‌صدا از کار نمی‌افتد.
 */
function evented_is_theme_settings_screen($hook = '')
{
    if ($hook === 'appearance_page_evented-theme-settings') {
        return true;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط تشخیص صفحه است، نه پردازش داده
    return isset($_GET['page']) && $_GET['page'] === 'evented-theme-settings';
}

/* ---------- بارگذاری رسانه + استایل اختصاصی فقط در همین صفحه ---------- */
add_action('admin_enqueue_scripts', function ($hook) {
    if (!evented_is_theme_settings_screen($hook)) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style('evented-theme-settings', PATH_DIR_URL . '/assets/css/admin/theme-settings.css', array(), '1.1.0');
    wp_enqueue_script('evented-theme-settings-js', PATH_DIR_URL . '/assets/js/admin/theme-settings.js', array('jquery'), '1.1.0', true);
    wp_localize_script('evented-theme-settings-js', 'eeSettings', array(
        'mediaTitle'    => 'انتخاب تصویر اسلایدر',
        'mediaButton'   => 'انتخاب تصویر',
        'mediaMissing'  => 'کتابخانهٔ رسانهٔ وردپرس بارگذاری نشد. صفحه را یک‌بار رفرش کنید یا نشانی تصویر را در فیلد «نشانی تصویر» وارد کنید.',
        'protoMissing'  => 'قالب ردیف اسلاید در صفحه پیدا نشد؛ صفحه را یک‌بار رفرش کنید.',
        'picked'        => 'تصویر انتخاب شد. برای ماندگاری، «ذخیرهٔ اسلایدرها» را بزنید.',
        'cleared'       => 'تصویر پاک شد. برای ماندگاری، «ذخیرهٔ اسلایدرها» را بزنید.',
        'urlSet'        => 'تصویر از نشانی وارد شد. برای ماندگاری، «ذخیرهٔ اسلایدرها» را بزنید.',
        'badUrl'        => 'نشانی تصویر باید با http یا // آغاز شود.',
        'confirmRemove' => 'این اسلاید از فهرست حذف شود؟ (با «ذخیرهٔ اسلایدرها» قطعی می‌شود)',
    ));
});

/* ---------- خواندن اسلایدهای ذخیره‌شده (مصرف front-end و صفحهٔ تنظیمات) ---------- */
function evented_get_home_slides()
{
    $slides = get_option(EVENTED_OPT_SLIDES, array());
    if (!is_array($slides)) {
        return array();
    }
    // نرمال‌سازی و حذف ردیف‌های بی‌تصویر
    $out = array();
    foreach ($slides as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id  = isset($row['id']) ? absint($row['id']) : 0;
        $img = isset($row['image']) ? esc_url_raw($row['image']) : '';
        if ($id && !$img) {
            // پیوست ممکن است بعد از ذخیره حذف شده باشد → نشانی را از پیوست بگیر
            $img = (string) wp_get_attachment_image_url($id, 'full');
        }
        if (!$img) {
            continue;
        }
        $out[] = array(
            'id'    => $id,
            'image' => $img,
            'title' => isset($row['title']) ? (string) $row['title'] : '',
            'desc'  => isset($row['desc']) ? (string) $row['desc'] : '',
            'badge' => isset($row['badge']) ? (string) $row['badge'] : '',
            'link'  => isset($row['link']) ? (string) $row['link'] : '',
        );
    }
    return $out;
}

/* ---------- ذخیره‌سازی ---------- */
function evented_render_theme_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $updated = false;

    if (isset($_POST['evented_theme_options_nonce'])) {
        check_admin_referer('save_evented_theme_options', 'evented_theme_options_nonce');

        $raw  = (isset($_POST['slides']) && is_array($_POST['slides'])) ? $_POST['slides'] : array();
        $saved = array();

        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id       = isset($row['image_id']) ? absint($row['image_id']) : 0;
            $image_url = isset($row['image_url']) ? esc_url_raw(wp_unslash($row['image_url'])) : '';
            $title    = isset($row['title']) ? sanitize_text_field(wp_unslash($row['title'])) : '';
            $desc     = isset($row['desc']) ? sanitize_textarea_field(wp_unslash($row['desc'])) : '';
            $badge    = isset($row['badge']) ? sanitize_text_field(wp_unslash($row['badge'])) : '';
            $link     = isset($row['link']) ? esc_url_raw(wp_unslash($row['link'])) : '';

            if ($id) {
                // انتخاب از کتابخانهٔ رسانه: نشانی از پیوست خوانده می‌شود
                $img = wp_get_attachment_image_url($id, 'full');
                if (!$img) {
                    continue; // پیوست حذف/نامعتبر شده باشد
                }
            } else {
                // ورود دستی نشانی (جایگزین وقتی کتابخانهٔ رسانه باز نمی‌شود)
                $img = $image_url;
            }

            if (!$img) {
                continue; // اسلاید بدون تصویر ذخیره نمی‌شود
            }

            $saved[] = array(
                'id'    => $id,
                'image' => $img,
                'title' => $title,
                'desc'  => $desc,
                'badge' => $badge,
                'link'  => $link,
            );
        }

        update_option(EVENTED_OPT_SLIDES, $saved, false);
        $updated = true;
    }

    $slides = evented_get_home_slides();
    ?>
    <div class="wrap evented-settings-wrap">
        <h1>تنظیمات قالب evented-edu</h1>
        <p class="description">
            اسلایدهای <strong>بنر بالای صفحهٔ اصلی (هیرو)</strong> را مدیریت کنید.
            برای هر اسلاید یک تصویر از کتابخانهٔ رسانه انتخاب کنید و در صورت تمایل عنوان، توضیح کوتاه، برچسب و لینک بدهید.
            اگر پنجرهٔ کتابخانهٔ رسانه باز نشد، می‌توانید نشانی تصویر را مستقیم در فیلد «نشانی تصویر» همان ردیف وارد کنید.
            اگر اسلایدی تنظیم نشود، بنر هیرو نمایش داده نمی‌شود و بخش «آخرین مقالات» تمام‌عرض می‌شود (هیچ محتوای جایگزینی ساخته نمی‌شود).
        </p>

        <?php if ($updated) : ?>
            <div class="notice notice-success is-dismissible"><p>✅ تغییرات با موفقیت ذخیره شد.</p></div>
        <?php endif; ?>

        <form method="post" action="" class="evented-slides-form">
            <?php wp_nonce_field('save_evented_theme_options', 'evented_theme_options_nonce'); ?>

            <h2 class="title">اسلایدر صفحهٔ اصلی</h2>

            <div id="evented-slides-list">
                <?php
                if (empty($slides)) {
                    echo '<p class="description" id="evented-slides-empty">هنوز اسلایدی اضافه نکرده‌اید. از دکمهٔ «افزودن اسلایدر» استفاده کنید.</p>';
                } else {
                    foreach ($slides as $i => $slide) {
                        echo evented_slide_row_html($i, $slide); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی داخل تابع escape شده است
                    }
                }
                ?>
            </div>

            <p>
                <button type="button" class="button button-primary" id="evented-add-slide">+ افزودن اسلایدر</button>
            </p>

            <?php submit_button('ذخیرهٔ اسلایدرها'); ?>
        </form>

        <script>
            /* watchdog: اگر اسکریپت مدیریت اسلایدرها بارگذاری نشد، بی‌صدا نمانیم */
            window.setTimeout(function () {
                if (window.eventedSlidesReady) {
                    return;
                }
                var wrap = document.querySelector('.evented-settings-wrap');
                if (!wrap || document.getElementById('evented-slides-error')) {
                    return;
                }
                var box = document.createElement('div');
                box.className = 'notice notice-error';
                box.id = 'evented-slides-error';
                box.innerHTML = '<p><strong>اسکریپت مدیریت اسلایدرها بارگذاری نشد</strong> — دکمهٔ «انتخاب تصویر» و «افزودن اسلایدر» کار نمی‌کنند. ' +
                    'معمولاً کش مرورگر یا یک افزونهٔ بهینه‌سازی/فشرده‌سازی اسکریپت‌ها سبب آن است. ' +
                    'اسلایدهای موجود همچنان قابل ویرایش و ذخیره هستند و می‌توانید نشانی تصویر را دستی در فیلد «نشانی تصویر» بگذارید.</p>';
                wrap.insertBefore(box, wrap.firstChild);
            }, 1500);
        </script>

        <!-- قالب یک ردیف اسلاید برای کپی توسط JS -->
        <?php
        echo evented_slide_row_html('__UID__', array());
        ?>
    </div>

    <?php
}

/**
 * HTML یک ردیف اسلاید (برای نمایش اولیه و قالبِ کپیِ JS)
 *
 * @param string        $uid   شناسهٔ یکتای ردیف (در قالب JS برابر __UID__ است)
 * @param array         $slide داده‌های اسلاید (می‌تواند خالی باشد)
 */
function evented_slide_row_html($uid, $slide = array())
{
    $image_url = !empty($slide['image']) ? $slide['image'] : '';
    $title     = isset($slide['title']) ? $slide['title'] : '';
    $desc      = isset($slide['desc']) ? $slide['desc'] : '';
    $badge     = isset($slide['badge']) ? $slide['badge'] : '';
    $link      = isset($slide['link']) ? $slide['link'] : '';
    $image_id  = isset($slide['id']) ? absint($slide['id']) : 0;

    $img_attrs = $image_url
        ? 'src="' . esc_url($image_url) . '" class="evented-slide-image has-image"'
        : 'class="evented-slide-image"';
    $box_class = $image_url ? '' : ' empty';
    $is_proto  = ($uid === '__UID__');
    $proto_attr = $is_proto ? ' id="evented-slide-proto" style="display:none"' : '';

    ob_start();
    ?>
    <div class="evented-slide-row" data-uid="<?php echo esc_attr($uid); ?>"<?php echo $proto_attr; ?>>
        <div class="evented-slide-main">
            <div class="evented-slide-imgbox<?php echo esc_attr($box_class); ?>">
                <img <?php echo $img_attrs; ?> alt="">
                <span class="evented-img-hint">تصویر اسلایدر</span>
                <input type="hidden" class="evented-slide-image-id" name="slides[<?php echo esc_attr($uid); ?>][image_id]" value="<?php echo esc_attr($image_id); ?>">
                <span class="evented-img-buttons">
                    <button type="button" class="button evented-pick-image">انتخاب تصویر</button>
                    <button type="button" class="button-link evented-remove-image">پاک کردن</button>
                </span>
            </div>

            <div class="evented-slide-fields">
                <p>
                    <label>نشانی تصویر (اختیاری — اگر کتابخانهٔ رسانه باز نشد، نشانی را اینجا بگذارید)</label>
                    <input type="text" dir="ltr" class="widefat evented-slide-image-url" name="slides[<?php echo esc_attr($uid); ?>][image_url]" value="<?php echo esc_url($image_url); ?>" placeholder="https://…">
                </p>
                <p>
                    <label>برچسب (اختیاری — روی تصویر نمایش داده می‌شود)</label>
                    <input type="text" class="widefat evented-slide-badge" name="slides[<?php echo esc_attr($uid); ?>][badge]" value="<?php echo esc_attr($badge); ?>" placeholder="مثلاً: دورهٔ ویژه">
                </p>
                <p>
                    <label>عنوان</label>
                    <input type="text" class="widefat evented-slide-title" name="slides[<?php echo esc_attr($uid); ?>][title]" value="<?php echo esc_attr($title); ?>" placeholder="عنوان بزرگ اسلایدر">
                </p>
                <p>
                    <label>توضیحات (اختیاری)</label>
                    <textarea class="widefat evented-slide-desc" name="slides[<?php echo esc_attr($uid); ?>][desc]" rows="2" placeholder="یک توضیح کوتاه زیر عنوان..."><?php echo esc_textarea($desc); ?></textarea>
                </p>
                <p>
                    <label>لینک دکمهٔ اسلایدر (اختیاری)</label>
                    <input type="text" dir="ltr" class="widefat evented-slide-link" name="slides[<?php echo esc_attr($uid); ?>][link]" value="<?php echo esc_attr($link); ?>" placeholder="https://…">
                </p>
            </div>
        </div>
        <div class="evented-slide-actions">
            <a href="#" class="evented-remove-slide">🗑 حذف این اسلایدر</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
