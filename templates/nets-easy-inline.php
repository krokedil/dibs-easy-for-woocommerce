<?php
/**
 * Nexi Checkout page
 *
 * Overrides /checkout/payment.php.
 *
 * @package dibs-easy-for-woocommerce
 */

do_action( 'wc_dibs_before_checkout_form' );
?>
<div class="form-row place-order">
	<div id="dibs-wrapper">
		<div id="dibs-iframe">
			<?php do_action( 'wc_dibs_before_snippet' ); ?>
			<div id="dibs-complete-checkout"></div>
			<?php do_action( 'wc_dibs_after_snippet' ); ?>
		</div>
	</div>
</div>

<?php
do_action( 'wc_dibs_after_checkout_form' );
?>
