<?php
/* Template Name: Panel - Profile */

if (! is_user_logged_in()) {
    // ریدایرکت به صفحه لاگین
    wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(home_url('/panel/profile')), home_url('/login')));
    exit;
}

$user_id = get_current_user_id();
get_template_part('template-parts/panel/shell', 'open', array('ee_panel_current' => 'profile', 'ee_panel_title' => 'پروفایل')); ?>
        <div class="profile-panel">
            <h2 class="section-title">اطلاعات پروفایل و گواهینامه</h2>
            
            <!-- فرم داینامیک -->
            <form id="profile-form">
                <?php wp_nonce_field('profile_nonce_action', 'profile_nonce'); ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>نام (فارسی)</label>
                        <input type="text" name="first_name_fa" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'first_name_fa', true)); ?>" placeholder="نام خود را به فارسی وارد کنید">
                    </div>
                    <div class="form-group">
                        <label>نام خانوادگی (فارسی)</label>
                        <input type="text" name="last_name_fa" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'last_name_fa', true)); ?>" placeholder="نام خانوادگی خود را به فارسی وارد کنید">
                    </div>
                    
                    <div class="form-group">
                        <label>نام (انگلیسی)</label>
                        <input type="text" name="first_name_en" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'first_name_en', true)); ?>" placeholder="نام خود را به انگلیسی وارد کنید" dir="ltr" style="text-align: right;">
                    </div>
                    <div class="form-group">
                        <label>نام خانوادگی (انگلیسی)</label>
                        <input type="text" name="last_name_en" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'last_name_en', true)); ?>" placeholder="نام خانوادگی خود را به انگلیسی وارد کنید" dir="ltr" style="text-align: right;">
                    </div>

                    <div class="form-group">
                        <label>جنسیت</label>
                        <?php $gender = get_user_meta($user_id, 'gender', true); ?>
                        <select name="gender" class="form-control">
                            <option value="" disabled <?php echo !$gender ? 'selected' : ''; ?>>انتخاب</option>
                            <option value="male" <?php echo $gender == 'male' ? 'selected' : ''; ?>>مرد</option>
                            <option value="female" <?php echo $gender == 'female' ? 'selected' : ''; ?>>زن</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>تاریخ تولد</label>
                        <input type="text" name="birth_date" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'birth_date', true)); ?>" placeholder="مثال: 1370/01/01" data-jdp>
                    </div>
                </div>

                <div class="form-footer">
                    <div class="info-alert">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="#00966D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 8V13" stroke="#00966D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M11.9941 16H12.0031" stroke="#00966D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        اطلاعات گواهی‌نامه پس از ثبت، قابل تغییر نخواهد بود.
                    </div>
                    <button type="submit" class="btn-submit">ذخیره اطلاعات</button>
                </div>
            </form>
            <div id="form-msg" style="margin-top:15px; text-align:center; font-weight:bold;"></div>
        </div>
        
<?php get_template_part('template-parts/panel/shell', 'close'); ?>
