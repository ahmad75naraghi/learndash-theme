<?php
/*
Template Name: صفحه لیست اساتید (Group Leaders)
*/
wp_enqueue_style('courses-archive-css', PATH_DIR_URL . '/assets/css/archive-courses.css', '', '1.0.0');

get_header();

// متغیرهای نمایشی صفحه (می‌توانید داینامیک کنید یا از ACF برگه بگیرید)
$page_title = "اساتید و متخصصان آموزشگاه آنلاین evented-edu"; // عنوان برگه‌ای که در وردپرس می‌سازید
$page_desc = 'در این صفحه با اساتید، مجریان و متخصصان برتر حوزه فناوری اطلاعات، شبکه و سخت‌افزار در آموزشگاه آنلاین evented-edu آشنا می‌شوید؛ مدرسانی باتجربه و فعال در پروژه‌های بزرگ سازمانی که دانش روز بازار کار را به صورت کاملاً کاربردی و سناریومحور به شما منتقل می‌کنند تا مسیر حرفه‌ای خود را با اطمینان بسازید.
';
?>

<main class="eduf-archive-main eduf-archive-instructor-main">
    <div class="eduf-container">

        <!-- مسیر راهنما (Breadcrumb) -->
        <nav class="eduf-breadcrumb">
            <a href="<?php echo home_url(); ?>">صفحه اصلی</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span class="current"><?php echo esc_html($page_title); ?></span>
        </nav>

        <!-- عنوان و توضیحات دسته -->
        <header class="eduf-category-header">
            <h1 class="eduf-title"><?php echo esc_html($page_title); ?></h1>
            <div class="eduf-desc">
                <?php echo wpautop(wp_kses_post($page_desc)); ?>
            </div>
        </header>

        <div class="eduf-layout-wrapper">

            <!-- سایدبار (فیلترها) - کدهای مشترک -->
            <aside id="eduf-sidebar" class="eduf-sidebar">
                <div class="container-eduf-sidebar">
                    <div class="container-filter-close">

                    <span class="close-eduf-sidebar">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </span>
                    <div class="eduf-filter-header">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.40039 2.20312H18.6004C19.7004 2.20312 20.6004 3.10312 20.6004 4.20312V6.40312C20.6004 7.20312 20.1004 8.20312 19.6004 8.70312L15.3004 12.5031C14.7004 13.0031 14.3004 14.0031 14.3004 14.8031V19.1031C14.3004 19.7031 13.9004 20.5031 13.4004 20.8031L12.0004 21.7031C10.7004 22.5031 8.90039 21.6031 8.90039 20.0031V14.7031C8.90039 14.0031 8.50039 13.1031 8.10039 12.6031L4.30039 8.60312C3.80039 8.10312 3.40039 7.20312 3.40039 6.60312V4.30312C3.40039 3.10312 4.30039 2.20312 5.40039 2.20312Z" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M10.9305 2.20312L6.00049 10.1031" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        فیلترها
                    </div>
                    </div>
                    <div class="eduf-widget-btn">
                        <button class="filter-submit">اعمال فیلتر</button>
                        <button class="filter-reset">حذف فیلتر</button>
                        </div>
                    <div class="eduf-widget">
                        <button class="eduf-widget-toggle active"> حوزه تخصصی استاد <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg></button>
                        <div class="eduf-widget-content" style="display: block;">
                            <label class="eduf-checkbox"><input type="checkbox"> <span> شبکه و زیرساخت (تجهیزات اکتیو و پسیو، مانیتورینگ)</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>سخت‌افزار و دیتاسنتر (سرور، استوریج، قطعات)</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>امنیت داده و سایبری (بکاپ‌گیری، فایروال، تست نفوذ)</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span> مدیریت و نرم‌افزارهای سازمانی (CRM، فرآیندهای سازمانی)</span></label>
                        </div>
                        
                    </div>
                    <div class="eduf-widget">
                        <button class="eduf-widget-toggle active">   سابقه کار اجرایی <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg></button>
                        <div class="eduf-widget-content" style="display: block;">
                            <label class="eduf-checkbox"><input type="checkbox"> <span>  بیش از ۵ سال سابقه</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span> بیش از ۱۰ سال سابقه</span></label>
                        </div>
                        
                    </div>
                </div>
                <div class="eduf-sidebar-overlay"></div>
            </aside>

            <!-- محتوای اصلی -->
            <section class="eduf-courses-content">

                <!-- نوار ابزار موبایل -->
                <div class="eduf-mobile-actions">
                    <button class="eduf-btn-mobile" data-active="eduf-sidebar"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.50028 1.83594H15.5003C16.417 1.83594 17.167 2.58594 17.167 3.5026V5.33594C17.167 6.0026 16.7503 6.83594 16.3336 7.2526L12.7503 10.4193C12.2503 10.8359 11.917 11.6693 11.917 12.3359V15.9193C11.917 16.4193 11.5836 17.0859 11.167 17.3359L10.0003 18.0859C8.91695 18.7526 7.41695 18.0026 7.41695 16.6693V12.2526C7.41695 11.6693 7.08362 10.9193 6.75028 10.5026L3.58362 7.16927C3.16695 6.7526 2.83362 6.0026 2.83362 5.5026V3.58594C2.83362 2.58594 3.58362 1.83594 4.50028 1.83594Z" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M9.10858 1.83594L5.00024 8.41927" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                        </svg> فیلترها</button>
                    <button class="eduf-btn-mobile" data-active="eduf-sorting"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.5 5.83398H17.5" stroke="black" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M5 10H15" stroke="black" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M8.33337 14.166H11.6667" stroke="black" stroke-width="1.5" stroke-linecap="round" />
                        </svg> مرتب سازی</button>
                </div>

                <!-- گرید اساتید -->
                <div class="eduf-grid eduf-grid-instructor">
                    <?php
                    // تنظیمات صفحه‌بندی برای کاربران
                    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                    $users_per_page = 12; // تعداد اساتید در هر صفحه

                    // کوئری گرفتن یوزرهایی که نقش group_leader دارند
                    $args = array(
                        'role'    => 'group_leader', // می‌توانید به جای این role_in قرار بدید اگر چند نقش دارید: array('group_leader', 'administrator')
                        'number'  => $users_per_page,
                        'paged'   => $paged,
                        'orderby' => 'display_name',
                        'order'   => 'ASC'
                    );

                    $user_query = new WP_User_Query($args);
                    $users = $user_query->get_results();

                    // دریافت تعداد کل برای صفحه‌بندی
                    $total_users = $user_query->get_total();
                    $total_pages = ceil($total_users / $users_per_page);

                    if (! empty($users)) {
                        foreach ($users as $user) {
                            // اطلاعات کاربر
                            $user_id = $user->ID;
                            $user_name = $user->display_name;
                            // لینک صفحه اختصاصی مدرس در لرن‌دش (معمولا لینک نویسنده است)
                            $author_url = get_author_posts_url($user_id);

                            // دریافت تصویر پروفایل کاربر (پیش‌فرض وردپرس) 
                            // اگر از افزونه‌ای برای آپلود آواتار استفاده میکنید ممکن است تابعش فرق کند
                            $avatar_url = get_avatar_url($user_id, array('size' => 300));

                            // دریافت بیوگرافی یا توضیحات استاد
                            $user_desc = get_the_author_meta('description', $user_id);
                            if (empty($user_desc)) {
                                $user_desc = 'لورم ایپسوم متن ساختگی با تولید سادگی...';
                            }
                    ?>
                            <!-- کارت استاد -->
                            <article class="eduf-instructor-card">
                                <a href="<?php echo esc_url($author_url); ?>" class="eduf-instructor-thumb">
                                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($user_name); ?>">
                                </a>
                                <h2 class="eduf-instructor-title">
                                    <a href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($user_name); ?></a>
                                </h2>
                                <div class="eduf-instructor-desc">
                                    <?php echo wp_trim_words($user_desc, 8, '...'); ?>
                                </div>
                            </article>
                    <?php
                        }
                    } else {
                        echo '<p>هیچ استادی یافت نشد.</p>';
                    }
                    ?>
                </div>

                <!-- صفحه‌بندی اختصاصی برای WP_User_Query -->
                <?php if ($total_pages > 1) : ?>
                    <div class="eduf-pagination">
                        <?php
                        $current_page = max(1, get_query_var('paged'));
                        echo paginate_links(array(
                            'base'      => get_pagenum_link(1) . '%_%',
                            'format'    => 'page/%#%/',
                            'current'   => $current_page,
                            'total'     => $total_pages,
                            'prev_text' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>',
                            'next_text' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>',
                        ));
                        ?>
                    </div>
                <?php endif; ?>

            </section>
        </div>

        <!-- بخش توضیحات سئو و سوالات متداول پایین صفحه -->
        <section class="eduf-bottom-content">
            <div class="eduf-seo-text">
            <p>یادگیری علم آی‌تی زمانی اثربخش است که زیر نظر اساتید مجرب و باسابقه هدایت شود. اساتید evented-edu بر اساس معیارهای سختی انتخاب می‌شوند تا بهترین کیفیت آموزشی را به شما ارائه دهند:</p>

<ul>
  <li><strong>سابقه اجرایی و عملیاتی:</strong> مدرسان ما صرفاً تئوری‌پرداز نیستند؛ بلکه سال‌ها در پروژه‌های واقعی شبکه، امنیت و سخت‌افزار سابقه کار اجرایی دارند.</li>
  <li><strong>تخصص در آخرین تکنولوژی‌ها:</strong> اساتید این مجموعه کاملاً به قطعات، سخت‌افزارها و نرم‌افزارهای مدرن (مانند سرورهای نسل جدید، ابزارهای پیشرفته مانیتورینگ و فایروال‌ها) مسلط هستند.</li>
  <li><strong>انتقال مفاهیم به زبان ساده:</strong> یکی از مهم‌ترین ویژگی‌های مدرسان evented-edu، توانایی ساده‌سازی مفاهیم پیچیده و سنگین دیتاسنتر و شبکه برای دانشجویان است.</li>
  <li><strong>پشتیبانی و پاسخگویی:</strong> اساتید در طول دوره و پس از آن، راهنما و پاسخگوی سوالات فنی و چالش‌های شما در فرآیند یادگیری خواهند بود.</li>
  <li><strong>مشاوره برای ورود به بازار کار:</strong> تجربه طولانی‌مدت اساتید در بازار کار ایران و بین‌الملل، دیدِ بازی به شما برای انتخاب مسیر شغلی درست می‌دهد.</li>
</ul>
            </div>
        </section>

    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.eduf-widget-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const content = button.nextElementSibling;
                button.classList.toggle('active');
                content.style.display = content.style.display === 'block' ? 'none' : 'block';
            });
        });
    });
</script>

<?php get_footer(); ?>