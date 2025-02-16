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

		if ( ! WC()->cart->needs_payment() ) {
			return $template;
		}

		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			return $template;
		}

		$checkout_flow = get_option( 'woocommerce_dibs_easy_settings' )['checkout_flow'] ?? 'embedded';
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
			$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
			if ( ! array_key_exists( 'dibs_easy', $available_gateways ) ) {
				return $template;
			}

			$maybe_template = locate_template( 'woocommerce/nets-easy-checkout.php' );
			$nexi_template  = $maybe_template ? $maybe_template : WC_DIBS_PATH . '/templates/nets-easy-checkout.php';

			$chosen_payment_method = WC()->session->chosen_payment_method;
			if ( 'dibs_easy' === $chosen_payment_method ) {
				return $nexi_template;
			}

			if ( empty( $chosen_payment_method ) && 'dibs_easy' === array_key_first( $available_gateways ) ) {
				return $nexi_template;
			}

			if ( ! isset( $available_gateways[ $chosen_payment_method ] ) && 'dibs_easy' === array_key_first( $available_gateways ) ) {
				return $nexi_template;
			}
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
