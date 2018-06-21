<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DIBS_Templates class.
 * 
 * @since 1.4.0
 * 
 */
class DIBS_Templates {

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
		add_filter( 'woocommerce_locate_template', array( $this, 'override_template' ), 999, 3 );

		// Template hooks.
		add_action( 'wc_dibs_before_checkout_form', 'wc_dibs_calculate_totals', 1 );
		add_action( 'wc_dibs_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
		add_action( 'wc_dibs_before_checkout_form', 'woocommerce_checkout_coupon_form', 20 );
		add_action( 'wc_dibs_after_order_review', 'wc_dibs_show_customer_order_notes', 10 );
		add_action( 'wc_dibs_after_order_review', 'wc_dibs_show_another_gateway_button', 20 );
		add_action( 'wc_dibs_after_order_review', 'wc_dibs_add_woocommerce_checkout_form_fields', 30 );
	}

	/**
	 * Override checkout form template if DIBS Easy is the selected payment method.
	 *
	 * @param string $template      Template.
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 *
	 * @return string
	 */
	public function override_template( $template, $template_name, $template_path ) {
		if ( is_checkout() ) {
			if ( 'checkout/form-checkout.php' === $template_name ) {
				$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

				if ( locate_template( 'woocommerce/dibs-checkout.php' ) ) {
					$dibs_easy_checkout_template = locate_template( 'woocommerce/dibs-easy-checkout.php' );
				} else {
					$dibs_easy_checkout_template = WC_DIBS_PATH . '/templates/dibs-easy-checkout.php';
				}

				// DIBS Easy checkout page.
				if ( array_key_exists( 'dibs_easy', $available_gateways ) ) {
					// If chosen payment method exists.
					if ( 'dibs_easy' === WC()->session->get( 'chosen_payment_method' ) ) {
						if ( ! isset( $_GET['paymentId'] ) ) {
							$template = $dibs_easy_checkout_template;
						}
					}

					// If chosen payment method does not exist and Easy is the first gateway.
					if ( null === WC()->session->get( 'chosen_payment_method' ) || '' === WC()->session->get( 'chosen_payment_method' ) ) {
						reset( $available_gateways );

						if ( 'dibs_easy' === key( $available_gateways ) ) {
							if ( ! isset( $_GET['paymentId'] ) ) {
								$template = $dibs_easy_checkout_template;
							}
						}
					}

					// If another gateway is saved in session, but has since become unavailable.
					if ( WC()->session->get( 'chosen_payment_method' ) ) {
						if ( ! array_key_exists( WC()->session->get( 'chosen_payment_method' ), $available_gateways ) ) {
							reset( $available_gateways );

							if ( 'dibs_easy' === key( $available_gateways ) ) {
								if ( ! isset( $_GET['paymentId'] ) ) {
									$template =  $dibs_easy_checkout_template;
								}
							}
						}
					}
				}
			}
		}

		return $template;
	}
}

DIBS_Templates::get_instance();