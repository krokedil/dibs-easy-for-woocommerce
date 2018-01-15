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

		} else {
			add_action( 'admin_notices', array( $this, 'check_terms' ) );
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
}
$wc_dibs_easy_admin_notices = new DIBS_Easy_Admin_Notices;