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

/* ---------- صفحهٔ تنظیمات (تب‌بندی) ---------- */
function evented_render_theme_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $updated = false;
    $active  = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'slides'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    /* ذخیرهٔ اسلایدها */
    if (isset($_POST['evented_theme_options_nonce'])) {
        check_admin_referer('save_evented_theme_options', 'evented_theme_options_nonce');

        $raw  = (isset($_POST['slides']) && is_array($_POST['slides'])) ? $_POST['slides'] : array();
        $saved = array();

        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id        = isset($row['image_id']) ? absint($row['image_id']) : 0;
            $image_url = isset($row['image_url']) ? esc_url_raw(wp_unslash($row['image_url'])) : '';
            $title     = isset($row['title']) ? sanitize_text_field(wp_unslash($row['title'])) : '';
            $desc      = isset($row['desc']) ? sanitize_textarea_field(wp_unslash($row['desc'])) : '';
            $badge     = isset($row['badge']) ? sanitize_text_field(wp_unslash($row['badge'])) : '';
            $link      = isset($row['link']) ? esc_url_raw(wp_unslash($row['link'])) : '';

            if ($id) {
                $img = wp_get_attachment_image_url($id, 'full');
                if (!$img) { continue; }
            } else {
                $img = $image_url;
            }
            if (!$img) { continue; }

            $saved[] = array('id' => $id, 'image' => $img, 'title' => $title, 'desc' => $desc, 'badge' => $badge, 'link' => $link);
        }

        update_option(EVENTED_OPT_SLIDES, $saved, false);
        $updated = true;
        $active  = 'slides';
    }

    /* ذخیرهٔ سایر تب‌ها */
    if (isset($_POST['evented_opts_nonce'])) {
        check_admin_referer('save_evented_opts', 'evented_opts_nonce');
        $input = (isset($_POST['ee']) && is_array($_POST['ee'])) ? $_POST['ee'] : array();
        // فقط فیلدهای تب جاری را به‌روز کن تا بقیه دست نخورند
        $tab_key = isset($_POST['ee_tab']) ? sanitize_key($_POST['ee_tab']) : '';
        $schema  = evented_options_schema();
        $current = get_option(EVENTED_OPT_KEY, array());
        $current = is_array($current) ? $current : array();
        if (isset($schema[$tab_key])) {
            $clean = evented_options_sanitize($input);
            foreach ($schema[$tab_key]['fields'] as $k => $f) {
                $current[$k] = $clean[$k];
            }
            update_option(EVENTED_OPT_KEY, $current, false);
            evented_options_flush();
            if (function_exists('evented_nav_flush_cache')) {
                evented_nav_flush_cache(true);
            }
            $updated = true;
            $active  = $tab_key;
        }
    }

    $slides = evented_get_home_slides();
    $schema = evented_options_schema();
    $values = array_merge(evented_options_defaults(), (array) get_option(EVENTED_OPT_KEY, array()));
    $base   = admin_url('themes.php?page=evented-theme-settings');
    ?>
    <div class="wrap evented-settings-wrap">
        <h1>تنظیمات قالب <?php echo esc_html(get_bloginfo('name')); ?></h1>

        <?php if ($updated) : ?>
            <div class="notice notice-success is-dismissible"><p>✅ تغییرات با موفقیت ذخیره شد.</p></div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper evented-tabs">
            <a href="<?php echo esc_url(add_query_arg('tab', 'slides', $base)); ?>" class="nav-tab<?php echo 'slides' === $active ? ' nav-tab-active' : ''; ?>"><span class="dashicons dashicons-images-alt2"></span> اسلایدر</a>
            <?php foreach ($schema as $tk => $tab) : ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $tk, $base)); ?>" class="nav-tab<?php echo $tk === $active ? ' nav-tab-active' : ''; ?>"><span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span> <?php echo esc_html($tab['title']); ?></a>
            <?php endforeach; ?>
        </h2>

        <?php if ('slides' === $active) : ?>
        <p class="description">
            اسلایدهای <strong>بنر بالای صفحهٔ اصلی (هیرو)</strong> را مدیریت کنید.
            اگر پنجرهٔ کتابخانهٔ رسانه باز نشد، می‌توانید نشانی تصویر را مستقیم در فیلد «نشانی تصویر» وارد کنید.
        </p>
        <form method="post" action="<?php echo esc_url(add_query_arg('tab', 'slides', $base)); ?>" class="evented-slides-form">
            <?php wp_nonce_field('save_evented_theme_options', 'evented_theme_options_nonce'); ?>
            <div id="evented-slides-list">
                <?php
                if (empty($slides)) {
                    echo '<p class="description" id="evented-slides-empty">هنوز اسلایدی اضافه نکرده‌اید. از دکمهٔ «افزودن اسلایدر» استفاده کنید.</p>';
                } else {
                    foreach ($slides as $i => $slide) {
                        echo evented_slide_row_html($i, $slide); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                }
                ?>
            </div>
            <p><button type="button" class="button button-primary" id="evented-add-slide">+ افزودن اسلایدر</button></p>
            <?php submit_button('ذخیرهٔ اسلایدرها'); ?>
        </form>
        <script>
            window.setTimeout(function () {
                if (window.eventedSlidesReady) { return; }
                var wrap = document.querySelector('.evented-settings-wrap');
                if (!wrap || document.getElementById('evented-slides-error')) { return; }
                var box = document.createElement('div');
                box.className = 'notice notice-error'; box.id = 'evented-slides-error';
                box.innerHTML = '<p><strong>اسکریپت مدیریت اسلایدرها بارگذاری نشد</strong> — می‌توانید نشانی تصویر را دستی وارد کنید.</p>';
                wrap.insertBefore(box, wrap.firstChild);
            }, 1500);
        </script>
        <?php echo evented_slide_row_html('__UID__', array()); // phpcs:ignore ?>

        <?php elseif (isset($schema[$active])) : $tab = $schema[$active]; ?>
        <form method="post" action="<?php echo esc_url(add_query_arg('tab', $active, $base)); ?>" class="evented-opts-form">
            <?php wp_nonce_field('save_evented_opts', 'evented_opts_nonce'); ?>
            <input type="hidden" name="ee_tab" value="<?php echo esc_attr($active); ?>">
            <?php if ('sms' === $active) : ?>
                <div class="notice notice-info inline"><p>برای امنیت بیشتر می‌توانید این مقادیر را به‌جای اینجا در <code>wp-config.php</code> تعریف کنید:
                <code>EVENTED_SMS_USERNAME</code>، <code>EVENTED_SMS_PASSWORD</code>، <code>EVENTED_SMS_BODY_ID</code>. ثابت‌ها بر مقدار ذخیره‌شده اولویت دارند.</p></div>
            <?php endif; ?>
            <table class="form-table" role="presentation">
                <?php foreach ($tab['fields'] as $k => $f) :
                    $v = $values[$k] ?? $f['default'];
                    $locked = !empty($f['const']) && defined($f['const']);
                    $id = 'ee_' . $k;
                    ?>
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($f['label']); ?></label></th>
                        <td>
                            <?php if ($locked) : ?>
                                <input type="text" class="regular-text" value="•••••• (از wp-config)" disabled>
                            <?php elseif ('textarea' === $f['type']) : ?>
                                <textarea id="<?php echo esc_attr($id); ?>" name="ee[<?php echo esc_attr($k); ?>]" rows="3" class="large-text"><?php echo esc_textarea((string) $v); ?></textarea>
                            <?php elseif ('checkbox' === $f['type']) : ?>
                                <label><input type="checkbox" id="<?php echo esc_attr($id); ?>" name="ee[<?php echo esc_attr($k); ?>]" value="1"<?php checked(!empty($v)); ?>> فعال</label>
                            <?php elseif ('number' === $f['type']) : ?>
                                <input type="number" id="<?php echo esc_attr($id); ?>" name="ee[<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr((string) $v); ?>" class="small-text"<?php echo isset($f['min']) ? ' min="' . (int) $f['min'] . '"' : ''; ?><?php echo isset($f['max']) ? ' max="' . (int) $f['max'] . '"' : ''; ?>>
                            <?php elseif ('password' === $f['type']) : ?>
                                <input type="password" id="<?php echo esc_attr($id); ?>" name="ee[<?php echo esc_attr($k); ?>]" value="" class="regular-text" dir="ltr" autocomplete="new-password" placeholder="<?php echo '' !== (string) $v ? '•••••••• (ذخیره شده؛ برای تغییر تایپ کنید)' : ''; ?>">
                            <?php else : ?>
                                <input type="<?php echo esc_attr('url' === $f['type'] ? 'url' : ('email' === $f['type'] ? 'email' : 'text')); ?>" id="<?php echo esc_attr($id); ?>" name="ee[<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr((string) $v); ?>" class="regular-text"<?php echo !empty($f['dir']) ? ' dir="' . esc_attr($f['dir']) . '"' : ''; ?><?php echo !empty($f['placeholder']) ? ' placeholder="' . esc_attr($f['placeholder']) . '"' : ''; ?>>
                            <?php endif; ?>
                            <?php if (!empty($f['desc'])) : ?><p class="description"><?php echo esc_html($f['desc']); ?></p><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button('ذخیرهٔ تنظیمات'); ?>
        </form>

        <?php if ('notify' === $active && function_exists('evented_notify')) : ?>
            <?php if (isset($_GET['ee_msg'])) : // phpcs:ignore ?>
                <div class="notice notice-<?php echo 'sent' === $_GET['ee_msg'] ? 'success' : 'warning'; // phpcs:ignore ?> is-dismissible"><p><?php echo 'sent' === $_GET['ee_msg'] ? esc_html(sprintf('اعلان برای %d کاربر ثبت شد.', (int) ($_GET['n'] ?? 0))) : 'عنوان اعلان خالی است.'; // phpcs:ignore ?></p></div>
            <?php endif; ?>
            <hr>
            <h2>ارسال اعلان دستی</h2>
            <p class="description">به همهٔ کاربران یا دانشجویان یک دوره اعلان بفرستید (در زنگولهٔ هدر نمایش داده می‌شود).</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="evented_notify_broadcast">
                <?php wp_nonce_field('evented_notify_broadcast'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="ee-nb-title">عنوان</label></th><td><input id="ee-nb-title" class="regular-text" type="text" name="title" required maxlength="190"></td></tr>
                    <tr><th scope="row"><label for="ee-nb-body">متن</label></th><td><textarea id="ee-nb-body" class="large-text" rows="3" name="body"></textarea></td></tr>
                    <tr><th scope="row"><label for="ee-nb-url">لینک (اختیاری)</label></th><td><input id="ee-nb-url" class="regular-text" type="url" name="url" dir="ltr"></td></tr>
                    <tr><th scope="row">گیرندگان</th><td>
                        <label><input type="radio" name="target" value="all" checked> همهٔ کاربران</label><br>
                        <label><input type="radio" name="target" value="course"> دانشجویان دوره:</label>
                        <select name="course_id">
                            <option value="0">— انتخاب دوره —</option>
                            <?php foreach (get_posts(array('post_type' => 'sfwd-courses', 'posts_per_page' => 300, 'orderby' => 'title', 'order' => 'ASC')) as $ee_c) : ?>
                                <option value="<?php echo (int) $ee_c->ID; ?>"><?php echo esc_html(get_the_title($ee_c)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th scope="row">پیامک</th><td><label><input type="checkbox" name="sms" value="1"> علاوه بر اعلان، پیامک هم ارسال شود (نیازمند الگوی اعلان و فعال بودن پیامک اعلان‌ها)</label></td></tr>
                </table>
                <?php submit_button('ارسال اعلان', 'secondary'); ?>
            </form>
        <?php endif; ?>
        <?php endif; ?>
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
