<?php
/**
 * Nets templates class.
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nets_Easy_Templates class.
 *
 * @since 1.4.0
 */
class Nets_Easy_Templates {

	/**
	 * The reference the *Singleton* instance of this class.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return self::$instance The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Plugin actions.
	 */
	public function __construct() {
		// Override template if DIBS Easy Checkout page.
		add_filter( 'wc_get_template', array( $this, 'override_template' ), 999, 2 );

		// Template hooks.
		add_action( 'wc_dibs_after_order_review', array( $this, 'add_extra_checkout_fields' ), 10 );
		add_action( 'wc_dibs_after_order_review', 'wc_dibs_show_another_gateway_button', 20 );
		add_action( 'wc_dibs_after_snippet', array( $this, 'add_wc_form' ), 10 );

		// Since Nexi Inline overrides the payment method template, we need to add a "Select another payment method" button.
		add_action( 'nexi_inline_after_snippet', 'wc_dibs_show_another_gateway_button' );
	}

	/**
	 *
	 * Checks whether Nexi is the selected payment method, or whether it should be considered selected.
	 *
	 * @return bool
	 */
	private function is_nexi_chosen() {
		// Ensure we have the properties required for our checks. This may not always be the case, e.g., on admin page. We return true in these situations to exit early, and to avoid disrupt existing behavior.
		if ( ! isset( WC()->session ) || ( ! method_exists( WC(), 'payment_gateways' ) || ! is_callable( array( WC(), 'payment_gateways' ) ) ) ) {
			return true;
		}

		// Before we make any additional controls, let us verify the gateway is registered.
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		if ( ! array_key_exists( 'dibs_easy', $available_gateways ) ) {
			return false;
		}

		$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );
		// If payment method doesn't exist, but Nexi is available, and is set to be the default, we can consider it as the chosen gateway.
		if ( empty( $chosen_payment_method ) ) {
			// Check the WC payment settings.
			if ( isset( $available_gateways[ $chosen_payment_method ] ) || 'dibs_easy' === array_key_first( $available_gateways ) ) {
				return true;
			}
		}

		// Check the session.
		if ( 'dibs_easy' === $chosen_payment_method ) {
			return true;
		}

		return false;
	}

	/**
	 * Override checkout form template if DIBS Easy is the selected payment method.
	 *
	 * @param string $template      Template.
	 * @param string $template_name Template name.
	 *
	 * @return string
	 */
	public function override_template( $template, $template_name ) {
		if ( ! is_checkout() ) {
			return $template;
		}

		// If the cart doesn't exist, this is probably the checkout edit page. To prevent the page from blanking, we'll just return the default template.
		if ( ! isset( WC()->cart ) || ! WC()->cart->needs_payment() ) {
			return $template;
		}

		if ( is_checkout_pay_page() ) {
			return $template;
		}

		if ( ! $this->is_nexi_chosen() ) {
			return $template;
		}

		$checkout_flow = get_option( 'woocommerce_dibs_easy_settings' )['checkout_flow'] ?? 'inline';
		if ( 'inline' === $checkout_flow ) {
			return $this->replace_payment_method( $template, $template_name );
		}

		return $this->maybe_replace_checkout( $template, $template_name );
	}

	/**
	 * Maybe replaces the entire checkout form template if Nexi is the selected payment method
	 *
	 * @param string $template The absolute path to the template.
	 * @param string $template_name The relative path to the template (known as the 'name').
	 * @return string
	 */
	public function maybe_replace_checkout( $template, $template_name ) {
		if ( 'checkout/form-checkout.php' === $template_name ) {

			$maybe_template = locate_template( 'woocommerce/nets-easy-checkout.php' );
			$nexi_template  = $maybe_template ? $maybe_template : WC_DIBS_PATH . '/templates/nets-easy-checkout.php';

			return $nexi_template;
		}

		return $template;
	}

	/**
	 * Replaces the payment method template only.
	 *
	 * @param string $template The absolute path to the template.
	 * @param string $template_name The relative path to the template (known as the 'name').
	 * @return string
	 */
	public function replace_payment_method( $template, $template_name ) {
		if ( 'checkout/payment.php' === $template_name ) {
			WC()->session->set( 'chosen_payment_method', 'dibs_easy' );
			// Retrieve the template for Nexi Checkout template.
			$maybe_template = locate_template( 'woocommerce/nets-easy-inline.php' );
			return $maybe_template ? $maybe_template : WC_DIBS_PATH . '/templates/nets-easy-inline.php';

		}

		return $template;
	}

	/**
	 * Adds the WC form and other fields to the checkout page.
	 *
	 * @return void
	 */
	public function add_wc_form() {
		?>
		<div aria-hidden="true" id="dibs-wc-form" style="position:absolute; top:0; left:-99999px;">
			<?php do_action( 'woocommerce_checkout_billing' ); ?>
			<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			<div id="dibs-nonce-wrapper">
				<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
			</div>
			<input id="payment_method_dibs_easy" type="radio" class="input-radio" name="payment_method" value="dibs_easy" checked="checked" />
		</div>
		<?php
	}

	/**
	 * Adds the extra checkout field div to the checkout page.
	 *
	 * @return void
	 */
	public function add_extra_checkout_fields() {
		?>
		<div id="dibs-extra-checkout-fields">
		</div>
		<?php
	}
}

Nets_Easy_Templates::get_instance();
