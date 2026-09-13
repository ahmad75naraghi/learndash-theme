<?php
/**
 * پایان پوستهٔ پنل کاربری — بستن گرید + مودال خروج + فوتر ee-*
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;
?>
		</section>
	</div>
</main>

<div class="ee-modal" id="eeLogoutModal" hidden>
	<div class="ee-modal-bg" data-ee-modal-close></div>
	<div class="ee-modal-box" role="dialog" aria-modal="true" aria-labelledby="eeLogoutTitle">
		<button type="button" class="ee-modal-x" data-ee-modal-close aria-label="بستن"><?php echo ee_icon('close'); // phpcs:ignore ?></button>
		<div class="ee-modal-ic"><?php echo ee_icon('logout'); // phpcs:ignore ?></div>
		<h3 id="eeLogoutTitle">از حساب کاربری خارج می‌شوید؟</h3>
		<p>اطلاعات و دوره‌های شما محفوظ می‌ماند؛ برای ادامه باید دوباره وارد شوید.</p>
		<div class="ee-modal-actions">
			<button type="button" class="ee-btn ee-btn-ghost" data-ee-modal-close>انصراف</button>
			<a href="#" class="ee-btn ee-btn-danger" id="eeLogoutConfirm">خروج از حساب</a>
		</div>
	</div>
</div>

<?php get_template_part('template-parts/ee', 'footer', array('ee_active' => 'account'));
