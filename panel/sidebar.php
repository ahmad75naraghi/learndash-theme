<?php $current = get_post_field('post_name', get_queried_object_id()); ?>
<aside class="sidebar">
    <div class="user-greeting<?= $current === 'panel' ? ' active' : ''; ?>">
        سلام <?php echo esc_attr(get_user_meta(get_current_user_id(), 'first_name', true)); ?>
    </div>

    <nav class="nav-menu">
        <a href="<?php echo esc_url(home_url('/panel/my-courses')); ?>" class="nav-item<?= $current === 'my-courses' ? ' active' : ''; ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22H15C20 22 22 20 22 15Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2.52002 7.11011H21.48" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8.52002 2.11011V6.97011" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.48 2.11011V6.52011" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9.75 14.4501V13.2501C9.75 11.7101 10.84 11.0801 12.17 11.8501L13.21 12.4501L14.25 13.0501C15.58 13.8201 15.58 15.0801 14.25 15.8501L13.21 16.4501L12.17 17.0501C10.84 17.8201 9.75 17.1901 9.75 15.6501V14.4501V14.4501Z" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            دوره های من
        </a>
        <a href="<?php echo esc_url(home_url('/panel/certificates')); ?>" class="nav-item<?= $current === 'certificates' ? ' active' : ''; ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 9C19 10.45 18.57 11.78 17.83 12.89C16.75 14.49 15.04 15.62 13.05 15.91C12.71 15.97 12.36 16 12 16C11.64 16 11.29 15.97 10.95 15.91C8.96 15.62 7.25 14.49 6.17 12.89C5.43 11.78 5 10.45 5 9C5 5.13 8.13 2 12 2C15.87 2 19 5.13 19 9Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21.25 18.4699L19.6 18.8599C19.23 18.9499 18.94 19.2299 18.86 19.5999L18.51 21.0699C18.32 21.8699 17.3 22.1099 16.77 21.4799L12 15.9999L7.22996 21.4899C6.69996 22.1199 5.67996 21.8799 5.48996 21.0799L5.13996 19.6099C5.04996 19.2399 4.75996 18.9499 4.39996 18.8699L2.74996 18.4799C1.98996 18.2999 1.71996 17.3499 2.26996 16.7999L6.16996 12.8999C7.24996 14.4999 8.95996 15.6299 10.95 15.9199C11.29 15.9799 11.64 16.0099 12 16.0099C12.36 16.0099 12.71 15.9799 13.05 15.9199C15.04 15.6299 16.75 14.4999 17.83 12.8999L21.73 16.7999C22.28 17.3399 22.01 18.2899 21.25 18.4699Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12.58 5.98L13.17 7.15999C13.25 7.31999 13.46 7.48 13.65 7.51L14.72 7.68999C15.4 7.79999 15.56 8.3 15.07 8.79L14.24 9.61998C14.1 9.75998 14.02 10.03 14.07 10.23L14.31 11.26C14.5 12.07 14.07 12.39 13.35 11.96L12.35 11.37C12.17 11.26 11.87 11.26 11.69 11.37L10.69 11.96C9.96997 12.38 9.53997 12.07 9.72997 11.26L9.96997 10.23C10.01 10.04 9.93997 9.75998 9.79997 9.61998L8.96997 8.79C8.47997 8.3 8.63997 7.80999 9.31997 7.68999L10.39 7.51C10.57 7.48 10.78 7.31999 10.86 7.15999L11.45 5.98C11.74 5.34 12.26 5.34 12.58 5.98Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            گواهینامه ها
        </a>
        <a href="<?php echo esc_url(home_url('/panel/wishlist')); ?>" class="nav-item<?= $current === 'wishlist' ? ' active' : ''; ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12.62 20.8096C12.28 20.9296 11.72 20.9296 11.38 20.8096C8.48 19.8196 2 15.6896 2 8.68961C2 5.59961 4.49 3.09961 7.56 3.09961C9.38 3.09961 10.99 3.97961 12 5.33961C13.01 3.97961 14.63 3.09961 16.44 3.09961C19.51 3.09961 22 5.59961 22 8.68961C22 15.6896 15.52 19.8196 12.62 20.8096Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            علاقه مندی ها
        </a>
        <a href="<?php echo esc_url(home_url('/panel/payments')); ?>" class="nav-item<?= $current === 'payments' ? ' active' : ''; ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3.92969 15.8792L15.8797 3.9292" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M11.1013 18.2791L12.3013 17.0791" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.793 15.5887L16.183 13.1987" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3.60127 10.239L10.2413 3.599C12.3613 1.479 13.4213 1.469 15.5213 3.569L20.4313 8.479C22.5313 10.579 22.5213 11.639 20.4013 13.759L13.7613 20.399C11.6413 22.519 10.5813 22.529 8.48127 20.429L3.57127 15.519C1.47127 13.419 1.47127 12.369 3.60127 10.239Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 21.9985H22" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            تراکنش ها
        </a>
        <a href="<?php echo esc_url(home_url('/panel/profile')); ?>" class="nav-item<?= $current === 'profile' ? ' active' : ''; ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18.1401 21.6198C17.2601 21.8798 16.2201 21.9998 15.0001 21.9998H9.00011C7.78011 21.9998 6.74011 21.8798 5.86011 21.6198C6.08011 19.0198 8.75011 16.9697 12.0001 16.9697C15.2501 16.9697 17.9201 19.0198 18.1401 21.6198Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15 2H9C4 2 2 4 2 9V15C2 18.78 3.14 20.85 5.86 21.62C6.08 19.02 8.75 16.97 12 16.97C15.25 16.97 17.92 19.02 18.14 21.62C20.86 20.85 22 18.78 22 15V9C22 4 20 2 15 2ZM12 14.17C10.02 14.17 8.42 12.56 8.42 10.58C8.42 8.60002 10.02 7 12 7C13.98 7 15.58 8.60002 15.58 10.58C15.58 12.56 13.98 14.17 12 14.17Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.58 10.58C15.58 12.56 13.98 14.17 12 14.17C10.02 14.17 8.42004 12.56 8.42004 10.58C8.42004 8.60002 10.02 7 12 7C13.98 7 15.58 8.60002 15.58 10.58Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            تنظیمات پروفایل
        </a>
        <a href="<?php echo esc_url(home_url('/panel/settings')); ?>" class="nav-item<?= $current === 'settings' ? ' active' : ''; ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 12.8799V11.1199C2 10.0799 2.85 9.21994 3.9 9.21994C5.71 9.21994 6.45 7.93994 5.54 6.36994C5.02 5.46994 5.33 4.29994 6.24 3.77994L7.97 2.78994C8.76 2.31994 9.78 2.59994 10.25 3.38994L10.36 3.57994C11.26 5.14994 12.74 5.14994 13.65 3.57994L13.76 3.38994C14.23 2.59994 15.25 2.31994 16.04 2.78994L17.77 3.77994C18.68 4.29994 18.99 5.46994 18.47 6.36994C17.56 7.93994 18.3 9.21994 20.11 9.21994C21.15 9.21994 22.01 10.0699 22.01 11.1199V12.8799C22.01 13.9199 21.16 14.7799 20.11 14.7799C18.3 14.7799 17.56 16.0599 18.47 17.6299C18.99 18.5399 18.68 19.6999 17.77 20.2199L16.04 21.2099C15.25 21.6799 14.23 21.3999 13.76 20.6099L13.65 20.4199C12.75 18.8499 11.27 18.8499 10.36 20.4199L10.25 20.6099C9.78 21.3999 8.76 21.6799 7.97 21.2099L6.24 20.2199C5.33 19.6999 5.02 18.5299 5.54 17.6299C6.45 16.0599 5.71 14.7799 3.9 14.7799C2.85 14.7799 2 13.9199 2 12.8799Z" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            تنظیمات حساب کاربری
        </a>
        
        <!-- تغییر مهم: لینک خروج به یک تریگر تبدیل شده و URL اصلی در data-url ذخیره شده است -->
        <a href="#" data-url="<?= esc_url(wp_logout_url('login')); ?>" class="nav-item logout-trigger">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8.90002 7.55999C9.21002 3.95999 11.06 2.48999 15.11 2.48999H15.24C19.71 2.48999 21.5 4.27999 21.5 8.74999V15.27C21.5 19.74 19.71 21.53 15.24 21.53H15.11C11.09 21.53 9.24002 20.08 8.91002 16.54" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15 12H3.62" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5.85 8.6499L2.5 11.9999L5.85 15.3499" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            خروج
        </a>
    </nav>
</aside>

<!-- کدهای HTML مربوط به مودال خروج -->
<div id="logout-modal-overlay" class="logout-modal-overlay">
    <div class="logout-modal-box">
        <button class="logout-modal-close" title="بستن">&times;</button>
        <div class="logout-modal-content">
            <h3 class="logout-modal-title">آیا می‌خواهید از حساب کاربری خارج شوید؟</h3>
            <p class="logout-modal-desc">با خروج از حساب، سبد خرید و اطلاعات شما ذخیره می‌ماند. برای ادامه خرید یا مشاهده سفارش‌ها باید دوباره وارد شوید.</p>
        </div>
        <div class="logout-modal-actions">
            <button class="logout-modal-cancel" id="cancel-logout-btn">انصراف</button>
            <button class="logout-modal-confirm" id="confirm-logout-btn">خروج از حساب</button>
        </div>
    </div>
</div>