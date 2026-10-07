const evented_ajax_url = window.eventedLogin.ajaxUrl;
const evented_auth_nonce = window.eventedLogin.nonce;
let otpTimerInterval = null;

// تشخیص تایپ فارسی
const persianRegex = /[\u0600-\u06FF\uFB8A\u067E\u0686\u06AF\u200C\u200F]/;

// تابع کمکی برای نمایش توست موفقیت
function showSuccessToast(message) {
    const toast = document.querySelector('.toast-card.success');
    const toastText = document.querySelector('.toast-card.success .toast-text');

    toastText.innerText = message;
    toast.classList.add('active');

    // پنهان شدن خودکار بعد از 3.5 ثانیه
    setTimeout(() => {
        toast.classList.remove('active');
    }, 5000);
}

// تابع کمکی برای نمایش توست خطا
function showErrorToast(message) {
    const toast = document.querySelector('.toast-card.error');
    const toastText = document.querySelector('.toast-card.error .toast-text');

    toastText.innerText = message;
    toast.classList.add('active');

    setTimeout(() => {
        toast.classList.remove('active');
    }, 5000);
}

// آبجکت وضعیت کلی برای جابجایی تستی بین کامپوننت ها
const state = {
    mobile: '',
    otpPurpose: null,
    firstName: '',
    lastName: ''
};

// المان‌های عمومی هدر و فیلدها
const btnToOtp = document.getElementById('btn-to-otp');
const mobileInput = document.getElementById('mobile-input');
const mobileError = document.getElementById('mobile-error');

mobileInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !btnToOtp.disabled) btnToOtp.click();
});
document.getElementById('login-password').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !document.getElementById('btn-login-submit').disabled) document.getElementById('btn-login-submit').click();
});
document.getElementById('first-name').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') document.getElementById('last-name').focus();
});
document.getElementById('last-name').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !document.getElementById('btn-save-name').disabled) document.getElementById('btn-save-name').click();
});
document.getElementById('new-password').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') document.getElementById('confirm-password').focus();
});
document.getElementById('confirm-password').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !document.getElementById('btn-save-password').disabled) document.getElementById('btn-save-password').click();
});

// کنترل فعالسازی دکمه مرحله اول
mobileInput.addEventListener('input', function() {
    const val = this.value.trim();
    // فقط عدد مجاز باشد
    this.value = val.replace(/[^0-9]/g, '');

    if (this.value.length === 11 && this.value.startsWith('09')) {
        btnToOtp.removeAttribute('disabled');
    } else {
        btnToOtp.setAttribute('disabled', 'true');
    }
});

// کلیک دکمه مرحله اول و هدایت هوشمند به مرحله بعد
btnToOtp.addEventListener('click', async function() {
    const phone = mobileInput.value.trim();
    state.mobile = phone;

    if (phone) {
        try {
            const formData = new FormData();
            formData.append('action', 'evented_check_mobile_and_send_otp');
            formData.append('mobile', phone);
            formData.append('nonce', evented_auth_nonce);
            const response = await fetch(evented_ajax_url, {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            if (res.success === true) {
                document.getElementById('user-phone-display').innerText = phone;
                if (res.data.status === 'login') {
                    state.otpPurpose = 'password_login';
                    goToStep('step-password-option');
                } else if (res.data.status === 'register') {
                    state.otpPurpose = 'register';
                    goToStep('step-otp');
                    startOtpTimer();
                }
            } else {
                showErrorToast(res.data.message);
            }


        } catch (error) {
            console.error('Ajax Error:', error);
            showErrorToast('خطا در برقراری ارتباط با سرور');
        }
    }
});

// لینک های جابجایی مستقیم در مرحله پسورد
document.getElementById('link-to-otp-direct').addEventListener('click', async (e) => {
    e.preventDefault();

    try {

        const formData = new FormData();
        formData.append('action', 'evented_send_otp');
        formData.append('mobile', state.mobile);
        formData.append('purpose', 'login_otp');
        formData.append('nonce', evented_auth_nonce);

        const response = await fetch(evented_ajax_url, {
            method: 'POST',
            body: formData
        });

        const res = await response.json();

        if (res.success) {

            document.getElementById('user-phone-display').innerText = state.mobile;
            state.otpPurpose = 'login_otp';
            goToStep('step-otp');
            startOtpTimer();

            showSuccessToast(res.data.message);

        } else {
            showErrorToast(res.data.message);
        }

    } catch (error) {
        console.error(error);
        showErrorToast('خطا در برقراری ارتباط با سرور');
    }
});

document.getElementById('link-to-reset').addEventListener('click', async (e) => {
    e.preventDefault();

    try {

        const formData = new FormData();
        formData.append('action', 'evented_send_otp');
        formData.append('mobile', state.mobile);
        formData.append('purpose', 'reset_password');
        formData.append('nonce', evented_auth_nonce);

        const response = await fetch(evented_ajax_url, {
            method: 'POST',
            body: formData
        });

        const res = await response.json();

        if (res.success) {

            document.getElementById('user-phone-display').innerText = state.mobile;

            // مشخص می‌کنیم OTP برای بازیابی رمز است
            state.otpPurpose = 'reset_password';

            goToStep('step-otp');
            startOtpTimer();

            showSuccessToast(res.data.message);

        } else {
            showErrorToast(res.data.message);
        }

    } catch (error) {
        console.error(error);
        showErrorToast('خطا در برقراری ارتباط با سرور');
    }
});

document.getElementById('link-to-password-direct').addEventListener('click', (e) => {
    e.preventDefault();
    goToStep('step-password-option');
});

// دکمه تایید ورود با پسورد معمولی
document.getElementById('btn-login-submit').addEventListener('click', async() => {
    const loginPass = document.getElementById('login-password').value;
    const btn = document.getElementById('btn-login-submit');

    // btn.innerText = 'در حال تایید کد...';
    btn.setAttribute('disabled', 'true');


    const formData = new FormData();
    formData.append('action', 'evented_login_user');
    formData.append('password', loginPass);
    formData.append('mobile', state.mobile);
    formData.append('nonce', evented_auth_nonce);
    const response = await fetch(evented_ajax_url, {
        method: 'POST',
        body: formData
    });

    btn.removeAttribute('disabled');

    const res = await response.json();

    if (res.success) {

        showSuccessToast(res.data.message);

        setTimeout(() => {
            window.location.href = window.eventedLogin.redirectTo
        }, 1000);

    } else {
        showErrorToast(res.data.message);
    }

});

// مدیریت اینپوت های ۵ گانه OTP پی در پی
const otpFields = document.querySelectorAll('.otp-field');
otpFields.forEach((field, index) => {
    field.addEventListener('input', (e) => {
        field.value = e.target.value.replace(/[^0-9]/g, '');
        if (field.value.length === 1 && index < otpFields.length - 1) {
            otpFields[index + 1].focus();
        }
        // بررسی برای ارسال خودکار کد
        let currentOtp = '';
        otpFields.forEach(input => currentOtp += input.value.trim());
        if (currentOtp.length === 5) {
            document.getElementById('btn-verify-otp').click();
        }
    });

    field.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && field.value.length === 0 && index > 0) {
            otpFields[index - 1].focus();
        }
    });
});

document.getElementById('btn-resend-otp').addEventListener('click', async () => {

    const resendBtn = document.getElementById('btn-resend-otp');

    resendBtn.disabled = true;
    resendBtn.innerText = 'در حال ارسال...';

    try {

        const formData = new FormData();
        formData.append('action', 'evented_send_otp');
        formData.append('mobile', state.mobile);
        formData.append('purpose', state.otpPurpose);
        formData.append('nonce', evented_auth_nonce);

        const response = await fetch(evented_ajax_url, {
            method: 'POST',
            body: formData
        });

        const res = await response.json();

        if (res.success) {

            showSuccessToast(res.data.message);

            resendBtn.style.display = 'none';

            document.getElementById('timer-clock').style.display = 'inline';
            document.getElementById('timer-text').style.display = 'inline';

            startOtpTimer();

        } else {
            showErrorToast(res.data.message);
        }

    } catch (error) {
        console.error(error);
        showErrorToast('خطا در برقراری ارتباط با سرور');
    }

    resendBtn.disabled = false;
    resendBtn.innerText = 'ارسال مجدد کد';

});

document.getElementById('btn-verify-otp').addEventListener('click', async() => {
    const btn = document.getElementById('btn-verify-otp');
    let otpCode = '';
    otpFields.forEach(input => otpCode += input.value.trim());

    if (otpCode.length < 5) {
        showErrorToast('لطفاً کد تایید ۵ رقمی را کامل وارد کنید');
        return;
    }

    btn.innerText = 'در حال تایید کد...';
    btn.setAttribute('disabled', 'true');


    const formData = new FormData();
    formData.append('action', 'evented_verify_otp');
    formData.append('otp', otpCode);
    formData.append('phone', state.mobile);
    formData.append('purpose', state.otpPurpose);
    formData.append('nonce', evented_auth_nonce);
    const response = await fetch(evented_ajax_url, {
        method: 'POST',
        body: formData
    });

    btn.innerText = 'تایید کد';
    btn.removeAttribute('disabled');

    const res = await response.json();

    if (res.success) {

        switch (state.otpPurpose) {

            case 'reset_password':
                goToStep('step-new-password');
                break;

            case 'register':
                goToStep('step-name');
                break;

            case 'login_otp':
                window.location.href = window.eventedLogin.redirectTo;
                break;
        }


    } else {
        // ارور اشتباه بودن کد تایید
        showErrorToast(res.data.message);
    }

});

const firstNameInput = document.getElementById('first-name');
const lastNameInput = document.getElementById('last-name');
const btnSaveName = document.getElementById('btn-save-name');

function validateName() {
    if (firstNameInput.value.trim() !== '' && lastNameInput.value.trim() !== '') {
        btnSaveName.removeAttribute('disabled');
    } else {
        btnSaveName.setAttribute('disabled', 'true');
    }
}
firstNameInput.addEventListener('input', validateName);
lastNameInput.addEventListener('input', validateName);

btnSaveName.addEventListener('click', async () => {
    state.firstName = firstNameInput.value.trim();
    state.lastName = lastNameInput.value.trim();

    btnSaveName.setAttribute('disabled', 'true');

    const formData = new FormData();
    formData.append('action', 'save_user_register_name');
    formData.append('mobile', state.mobile);
    formData.append('firstname', state.firstName);
    formData.append('lastname', state.lastName);
    formData.append('nonce', evented_auth_nonce);
    const response = await fetch(evented_ajax_url, {
        method: 'POST',
        body: formData
    });

    btnSaveName.removeAttribute('disabled');

    const res = await response.json();

    if (res.success) {
        goToStep('step-new-password');
    } else {
        // ارور اشتباه بودن کد تایید
        showErrorToast(res.data.message);
    }

    document.getElementById('new-password').focus();
});

// اعتبارسنجی زنده پسورد و نمایش قدرت امنیت آن
const newPassword = document.getElementById('new-password');
const confirmPassword = document.getElementById('confirm-password');
const btnSavePassword = document.getElementById('btn-save-password');

const rules = {
    number: /[0-9]/,
    length: /^.{8,}$/,
    case: /^(?=.*[a-z])(?=.*[A-Z])/,
    special: /[@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/
};

function preventPersianChars(e) {
    if (persianRegex.test(e.target.value)) {
        showErrorToast('لطفاً کیبورد خود را انگلیسی کنید و از حروف فارسی استفاده نکنید');
        e.target.value = e.target.value.replace(/[\u0600-\u06FF\uFB8A\u067E\u0686\u06AF\u200C\u200F]/g, '');
    }
}

newPassword.addEventListener('input', function(e) {
    preventPersianChars(e);

    const val = this.value;
    let score = 0;

    if (rules.number.test(val)) {
        document.getElementById('rule-number').className = 'valid';
        score++;
    } else {
        document.getElementById('rule-number').className = 'invalid';
    }

    if (rules.length.test(val)) {
        document.getElementById('rule-length').className = 'valid';
        score++;
    } else {
        document.getElementById('rule-length').className = 'invalid';
    }

    if (rules.case.test(val)) {
        document.getElementById('rule-case').className = 'valid';
        score++;
    } else {
        document.getElementById('rule-case').className = 'invalid';
    }

    if (rules.special.test(val)) {
        document.getElementById('rule-special').className = 'valid';
        score++;
    } else {
        document.getElementById('rule-special').className = 'invalid';
    }

    const txt = document.getElementById('strength-text');
    const b1 = document.getElementById('bar-1');
    const b2 = document.getElementById('bar-2');
    const b3 = document.getElementById('bar-3');

    b1.style.backgroundColor = b2.style.backgroundColor = b3.style.backgroundColor = 'var(--primary-disabled)';

    if (score <= 1 && val.length > 0) {
        txt.innerText = 'ضعیف';
        txt.className = 'strength-label weak';
        b1.style.backgroundColor = 'var(--error-color)';
    } else if (score >= 2 && score <= 3) {
        txt.innerText = 'متوسط';
        txt.className = 'strength-label medium';
        b1.style.backgroundColor = '#F4B740';
        b2.style.backgroundColor = '#F4B740';
    } else if (score === 4) {
        txt.innerText = 'قوی';
        txt.className = 'strength-label strong';
        b1.style.backgroundColor = 'var(--success-color)';
        b2.style.backgroundColor = 'var(--success-color)';
        b3.style.backgroundColor = 'var(--success-color)';
    } else {
        txt.innerText = '';
    }

    validatePasswordMatch();
});

confirmPassword.addEventListener('input', validatePasswordMatch);

function validatePasswordMatch() {
    const allValid = document.querySelectorAll('.password-rules .valid').length === 4;
    if (allValid && newPassword.value === confirmPassword.value && confirmPassword.value.length > 0) {
        btnSavePassword.removeAttribute('disabled');
    } else {
        btnSavePassword.setAttribute('disabled', 'true');
    }
}

// دکمه ذخیره و تغییر نهایی رمز عبور
document.getElementById('btn-save-password').addEventListener('click', async() => {
    const btn = document.getElementById('btn-save-password');
    let otpCode = '';

    btn.innerText = 'در حال ارسال...';
    btn.setAttribute('disabled', 'true');

    const password = newPassword.value.trim();
    const confirmPass = confirmPassword.value.trim();

    let actionName = 'evented_register_user';

    if (state.otpPurpose === 'reset_password') {
        actionName = 'evented_reset_password';
    }

    const formData = new FormData();
    formData.append('action', actionName);
    formData.append('phone', state.mobile);
    formData.append('password', password);
    formData.append('confirmPassword', confirmPass);
    formData.append('nonce', evented_auth_nonce);
    const response = await fetch(evented_ajax_url, {
        method: 'POST',
        body: formData
    });

    btn.innerText = state.otpPurpose === 'reset_password' ? 'تغییر رمز عبور' : 'تکمیل ثبت‌نام';
    btn.removeAttribute('disabled');

    const res = await response.json();

    if (res.success) {
        showSuccessToast(res.data.message);

        if (res.data.result === 'login') {
            // کاربر از قبل ثبت نام کرده بود و لاگین شد -> انتقال به صفحه اصلی
            setTimeout(() => window.location.href = window.eventedLogin.redirectTo, 1500);
        } else if (res.data.is_new_user === true) {
            // کاربر جدید است -> هدایت به بخش تعیین پسورد
            goToStep('step-name');
        }
    } else {
        // ارور اشتباه بودن کد تایید
        showErrorToast(res.data.message);
    }
});

// توابع کمکی کاربردی عمومی
function goToStep(stepId) {
    document.querySelectorAll('.auth-step').forEach(step => step.classList.remove('active'));
    document.getElementById(stepId).classList.add('active');
    document.querySelectorAll('.toast-card').forEach((e)=>{
        e.classList.remove('active')
    });
    if (stepId === 'step-otp') {
        setTimeout(() => {
            otpFields.forEach(field => field.value = ''); // اختیاری: پاک کردن مقادیر قبلی
            otpFields[0].focus();
        }, 100);
    }
}

document.querySelectorAll('[data-password-target]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordTarget);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        input.classList.toggle('show-password', input.type === 'text');
        button.setAttribute('aria-pressed', input.type === 'text' ? 'true' : 'false');
    });
});

function startOtpTimer() {

    if (otpTimerInterval) {
        clearInterval(otpTimerInterval);
    }

    const resendBtn = document.getElementById('btn-resend-otp');
    const timerClock = document.getElementById('timer-clock');
    const timerText = document.getElementById('timer-text');

    resendBtn.style.display = 'none';
    timerClock.style.display = 'inline';
    timerText.style.display = 'inline';

    let duration = 120;

    updateDisplay();

    otpTimerInterval = setInterval(() => {

        duration--;

        if (duration <= 0) {

            clearInterval(otpTimerInterval);

            timerClock.style.display = 'none';
            timerText.style.display = 'none';

            resendBtn.style.display = 'block';

            return;
        }

        updateDisplay();

    }, 1000);

    function updateDisplay() {

        const minutes = String(Math.floor(duration / 60)).padStart(2, '0');
        const seconds = String(duration % 60).padStart(2, '0');

        timerClock.innerText = `${minutes}:${seconds}`;
    }
}
