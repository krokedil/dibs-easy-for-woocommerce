<?php
/**
 * Nexi Checkout page
 *
 * Overrides /checkout/payment.php.
 *
 * @package dibs-easy-for-woocommerce
 */

?>
<div id="nexi-inline-modal">
	<div id="nexi-inline-modal-box">
		<span id="nexi-inline-close-modal" class="close-netseasy-modal">&times;</span>
		<div class="form-row place-order">
			<input type="hidden" name="payment_method" value="dibs_easy" />
			<?php
			if ( version_compare( WOOCOMMERCE_VERSION, '3.4', '<' ) ) {
				wp_nonce_field( 'woocommerce-process_checkout' );
			} else {
				wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );
			}
			?>
			<div id="dibs-wrapper">
				<div id="dibs-iframe">
					<?php do_action( 'nexi_inline_before_snippet' ); ?>
					<div id="dibs-complete-checkout"></div>
					<?php do_action( 'nexi_inline_after_snippet' ); ?>
				</div>
			</div>
		</div>
	</div>
</div>