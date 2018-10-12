<?php
/**
 * DIBS Easy Checkout page
 *
 * Overrides /checkout/form-checkout.php.
 *
 * @package dibs-easy-for-woocommerce
 */

do_action( 'woocommerce_before_checkout_form', $checkout );
do_action( 'wc_dibs_before_checkout_form' );
?>

<form name="checkout" class="checkout woocommerce-checkout">
	<div id="dibs-wrapper">
		<div id="dibs-order-review">
			<?php do_action( 'wc_dibs_before_order_review' ); ?>
			<?php woocommerce_order_review(); ?>
			<?php do_action( 'wc_dibs_after_order_review' ); ?>
		</div>

		<div id="dibs-iframe">
			<?php do_action( 'wc_dibs_before_snippet' ); ?>
			<?php wc_dibs_show_snippet(); ?>
			<?php do_action( 'wc_dibs_after_snippet' ); ?>
		</div>
	</div>
</form>

<?php
do_action( 'wc_dibs_after_checkout_form' );
do_action( 'woocommerce_after_checkout_form', $checkout );
?>
