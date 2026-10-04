<?php
/* Template Name: Panel - Payments */

if ( ! is_user_logged_in() ) {
    wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(home_url('/panel/payments')), home_url('/login')));
    exit;
}

$current_user_id = get_current_user_id();

// دریافت تراکنش‌های کاربر از جدول اختصاصی
global $wpdb;
$table_name         = $wpdb->prefix . 'evented_transactions';
$transactions       = array();
$transactions_total = 0;
$transactions_page  = max(1, isset($_GET['tx_page']) ? absint($_GET['tx_page']) : 1); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط صفحه‌بندی نمایشی.
$transactions_per_page = 20;
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name))) === $table_name) {
    $transactions_total = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
        $current_user_id
    ));
    $transactions_pages = max(1, (int) ceil($transactions_total / $transactions_per_page));
    $transactions_page  = min($transactions_page, $transactions_pages);
    $transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT course_id, status, created_at, tracking_code, amount FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $current_user_id,
        $transactions_per_page,
        ($transactions_page - 1) * $transactions_per_page
    ));
} else {
    $transactions_pages = 1;
}

get_template_part('template-parts/panel/shell', 'open', array('ee_panel_current' => 'payments', 'ee_panel_title' => 'تراکنش‌ها')); ?>
        <div class="transaction-content">
            <h2 class="section-title">تراکنش ها</h2>

            <?php if ( ! empty($transactions) ) : ?>
                <div class="table-header">
                    <div>عنوان دوره</div>
                    <div>وضعیت تراکنش</div>
                    <div>تاریخ تراکنش</div>
                    <div>شماره تراکنش</div>
                    <div>مبلغ تراکنش</div>
                    <div>رسید</div>
                </div>
                <?php foreach ( $transactions as $tx ) : 
                    
                    // دریافت عنوان دوره از لرن‌دش
                    $course_title = get_the_title( $tx->course_id );
                    if ( empty($course_title) ) $course_title = 'دوره نامشخص (حذف شده)';

                    // پردازش وضعیت‌های رایج درگاه؛ مقدار ناشناخته با متن امن نمایش داده می‌شود.
                    $status_map = array(
                        'success'   => array('status-success', 'موفق'),
                        'pending'   => array('status-pending', 'در انتظار'),
                        'refunded'  => array('status-refunded', 'بازگشت وجه'),
                        'cancelled' => array('status-fail', 'لغوشده'),
                        'failed'    => array('status-fail', 'ناموفق'),
                    );
                    $status_data  = isset($status_map[$tx->status]) ? $status_map[$tx->status] : array('status-fail', 'ناموفق');
                    $is_success   = ('success' === $tx->status);
                    $status_class = $status_data[0];
                    $status_text  = $status_data[1];
                    
                    // پردازش دکمه
                    $btn_class = $is_success ? 'active' : 'disabled';
                    $btn_attr  = $is_success ? '' : 'disabled';

                    // تبدیل تاریخ میلادی دیتابیس به فرمت نمایشی (اگر افزونه شمسی‌ساز مثل wp-parsidate دارید، خودکار شمسی می‌شود)
                    $timestamp = strtotime($tx->created_at);
                    $date_display = function_exists('evented_jalali_format') ? evented_jalali_format($timestamp, 'Y/m/d', true) : wp_date('Y/m/d', $timestamp);
                    $time_display = wp_date('H:i:s', $timestamp);

                    // فرمت مبلغ
                    $amount_display = number_format( $tx->amount ) . ' تومان';
                ?>
                    <div class="table-row">
                        <div><?php echo esc_html( $course_title ); ?></div>
                        <div class="<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_text ); ?></div>
                        <div class="datetime"><time datetime="<?php echo esc_attr(gmdate('c', $timestamp)); ?>"><?php echo esc_html($date_display); ?><span><?php echo esc_html($time_display); ?></span></time></div>
                        <div><?php echo esc_html( $tx->tracking_code ); ?></div>
                        <div><?php echo esc_html( $amount_display ); ?></div>
                        <div>
                            <button type="button" class="btn-receipt <?php echo esc_attr($btn_class); ?>" <?php echo $btn_attr; ?> aria-haspopup="dialog"
                                data-course="<?php echo esc_attr($course_title); ?>"
                                data-price="<?php echo esc_attr($amount_display); ?>"
                                data-date="<?php echo esc_attr($date_display); ?>"
                                data-time="<?php echo esc_attr($time_display); ?>"
                                data-track="<?php echo esc_attr($tx->tracking_code); ?>">
                                دانلود رسید
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php echo function_exists('evented_panel_pagination') ? evented_panel_pagination($transactions_page, $transactions_pages, 'tx_page') : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php else : ?>
                <div class="no-exist-notice ee-u-grid-empty">
                    <p>
                        هنوز دوره ای شرکت نکردی!!
                    </p>
                    <p>
                            هر پرداختی که انجام دهی، همراه با رسید و کد رهگیری همین‌جا ثبت می‌شود.
                        </p>
                    <a class="btn-primary" href="<?php echo esc_url(get_post_type_archive_link('sfwd-courses') ?: home_url('/')); ?>">
                        مشاهده دوره ها
                    </a>
                </div>
            <?php endif; ?>

        </div>
        
        <!-- Modal Overlay (مخفی به صورت پیش‌فرض) -->
        <div class="overlay">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="eeReceiptTitle">
                <div class="modal-header">
                    <div class="modal-title" id="eeReceiptTitle">جزئیات فاکتور شما</div>
                    <button type="button" class="btn-close" aria-label="بستن رسید">✕</button>
                </div>
                
                <table class="invoice-table">
                    <tr>
                        <td>دوره</td>
                        <td id="modal-course">---</td>
                    </tr>
                    <tr>
                        <td>قیمت</td>
                        <td class="price-text" id="modal-price">---</td>
                    </tr>
                    <tr>
                        <td>تاریخ</td>
                        <td id="modal-date">---</td>
                    </tr>
                    <tr>
                        <td>ساعت</td>
                        <td id="modal-time">---</td>
                    </tr>
                    <tr>
                        <td>شماره پیگیری</td>
                        <td id="modal-track">---</td>
                    </tr>
                </table>

                <button type="button" class="btn-download-full" onclick="window.print()">چاپ / دانلود رسید</button>
                
                <div class="support-text">
                    سوالی دارید؟ با ما تماس بگیرید: <a href="tel:02183636">۰۲۱۸۳۶۳۶</a>
                </div>
            </div>
        </div>
    

<?php get_template_part('template-parts/panel/shell', 'close'); ?>
