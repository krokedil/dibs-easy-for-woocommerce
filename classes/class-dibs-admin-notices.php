<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Returns error messages depending on
 *
 * @class    DIBS_Easy_Admin_Notices
 * @version  1.0
 * @package  DIBS_Easy/Classes
 * @category Class
 * @author   Krokedil
 */
class DIBS_Easy_Admin_Notices {
	/**
	 * DIBS_Easy_Admin_Notices constructor.
	 */
	public function __construct() {
		$dibs_easy_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$this->enabled           = $dibs_easy_settings['enabled'];
		add_action( 'admin_init', array( $this, 'check_settings' ) );
	}
	public function check_settings() {
		if ( ! empty( $_POST ) ) {
			add_action( 'woocommerce_settings_saved', array( $this, 'check_terms' ) );
			add_action( 'woocommerce_settings_saved', array( $this, 'check_account' ) );

		} else {
			add_action( 'admin_notices', array( $this, 'check_terms' ) );
			add_action( 'admin_notices', array( $this, 'check_https' ) );
			add_action( 'admin_notices', array( $this, 'check_account' ) );
		}
	}
	/**
	 * Check if terms page is set
	 */
	public function check_terms() {
		if ( 'yes' != $this->enabled ) {
			return;
		}
		// Terms page
		if ( ! wc_get_page_id( 'terms' ) || wc_get_page_id( 'terms' ) < 0 ) {
			echo '<div class="notice notice-error">';
			echo '<p>' . sprintf(__( 'You need to <a href="%s" target="_blank">specify a terms page</a> in WooCommerce Settings to be able to use DIBS Easy.', 'dibs-easy-for-woocommerce' ), 'https://docs.woocommerce.com/document/configuring-woocommerce-settings/#section-14') . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Check if https is configured.
	 */
	public function check_https() {
		if ( 'yes' !== $this->enabled ) {
			return;
		}
		if ( ! is_ssl() ) {
			echo '<div class="notice notice-error">';
			echo '<p>' . esc_html( __( 'You need to enable and configure https to be able to use DIBS Easy.', 'dibs-easy-for-woocommerce' ) ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Check how account creation is set.
	 */
	public function check_account() {
		if ( 'yes' !== $this->enabled ) {
			return;
		}
		// Account page - username.
		if ( 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) && 'no' === get_option( 'woocommerce_registration_generate_username' ) ) {
			echo '<div class="notice notice-error">';
			echo '<p>' . sprintf( __( 'To be able to use DIBS Easy correctly you need to tick the checkbox <i>When creating an account, automatically generate a username from the customer\'s email address</i> when having the <i>Allow customers to create an account during checkout</i> setting activated. This can be changed in the <a href="%s">Accounts & Privacy tab</a>.', 'dibs-easy-for-woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=account' ) ) . '</p>';
			echo '</div>';
		}
		// Account page - password.
		if ( 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) && 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
			echo '<div class="notice notice-error">';
			echo '<p>' . sprintf( __( 'To be able to use DIBS Easy correctly you need to tick the checkbox <i>When creating an account, automatically generate an account password</i> when having the <i>Allow customers to create an account during checkout</i> setting activated. This can be changed in the <a href="%s">Accounts & Privacy tab</a>.', 'dibs-easy-for-woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=account' ) ) . '</p>';
			echo '</div>';
		}
	}
}
$wc_dibs_easy_admin_notices = new DIBS_Easy_Admin_Notices;