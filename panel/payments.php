<?php
/* Template Name: Panel - Payments */

if ( ! is_user_logged_in() ) {
    wp_redirect(add_query_arg('redirect_to', home_url('/panel/payments'), wp_login_url()));
    exit;
}

$current_user_id = get_current_user_id();

// دریافت تراکنش‌های کاربر از جدول اختصاصی
global $wpdb;
$table_name = $wpdb->prefix . 'evented_transactions';
$transactions = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC", 
    $current_user_id
) );

get_header(); ?>

<div class="container">

    <!-- Sidebar -->
    <?php include_once 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
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

                    // پردازش وضعیت
                    $is_success = ($tx->status === 'success');
                    $status_class = $is_success ? 'status-success' : 'status-fail';
                    $status_text  = $is_success ? 'موفق' : 'ناموفق';
                    
                    // پردازش دکمه
                    $btn_class = $is_success ? 'active' : 'disabled';
                    $btn_attr  = $is_success ? '' : 'disabled';

                    // تبدیل تاریخ میلادی دیتابیس به فرمت نمایشی (اگر افزونه شمسی‌ساز مثل wp-parsidate دارید، خودکار شمسی می‌شود)
                    $timestamp = strtotime($tx->created_at);
                    $date_display = wp_date('Y/m/d', $timestamp);
                    $time_display = wp_date('H:i:s', $timestamp);
                    $full_date_display = $date_display . '<span>' . $time_display . '</span>';

                    // فرمت مبلغ
                    $amount_display = number_format( $tx->amount ) . ' تومان';
                ?>
                    <div class="table-row">
                        <div><?php echo esc_html( $course_title ); ?></div>
                        <div class="<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_text ); ?></div>
                        <div class="datetime"><?php echo $full_date_display; ?></div>
                        <div><?php echo esc_html( $tx->tracking_code ); ?></div>
                        <div><?php echo esc_html( $amount_display ); ?></div>
                        <div>
                            <button class="btn-receipt <?php echo esc_attr($btn_class); ?>" <?php echo $btn_attr; ?>
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
            <?php else : ?>
                <div class="no-exist-notice" style="grid-column: 1 / -1; padding: 20px; text-align: center; color: #666;">
                    <p>
                        هنوز دوره ای شرکت نکردی!!
                    </p>
                    <p>
                        لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است 
                    </p>
                    <a class="btn-primary" href="/">
                        مشاهده دوره ها
                    </a>
                </div>
            <?php endif; ?>

        </div>
        
        <!-- Modal Overlay (مخفی به صورت پیش‌فرض) -->
        <div class="overlay" style="display: none;">
            <div class="modal">
                <div class="modal-header">
                    <div class="modal-title">جزئیات فاکتور شما</div>
                    <button class="btn-close">✕</button>
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

                <button class="btn-download-full" onclick="window.print()">چاپ / دانلود رسید</button>
                
                <div class="support-text">
                    سوالی دارید؟ با ما تماس بگیرید: <a href="tel:02183636">۰۲۱۸۳۶۳۶</a>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- اسکریپت کنترل مُدال و انتقال داده‌ها -->
<script>
jQuery(document).ready(function($) {
    
    // باز کردن مُدال و تزریق اطلاعات فاکتور
    $('.btn-receipt.active').on('click', function() {
        var btn = $(this);
        
        // خواندن دیتا اتریبیوت‌ها از دکمه کلیک شده
        $('#modal-course').text(btn.data('course'));
        $('#modal-price').text(btn.data('price'));
        $('#modal-date').text(btn.data('date'));
        $('#modal-time').text(btn.data('time'));
        $('#modal-track').text(btn.data('track'));
        
        // نمایش پاپ‌آپ (فرض بر این است که CSS شما overlay را position: fixed کرده است)
        $('.overlay').css("display","flex");
    });

    // بستن مُدال با دکمه ضربدر
    $('.btn-close').on('click', function() {
        $('.overlay').fadeOut(200);
    });

    // بستن مُدال در صورت کلیک روی فضای خالی بک‌گراند
    $('.overlay').on('click', function(e) {
        if ($(e.target).hasClass('overlay')) {
            $(this).fadeOut(200);
        }
    });

});
</script>

<?php get_footer(); ?>