<?php
namespace Krokedil\Nexi\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Renames the stored title of the standalone MobilePay payment method to Vipps.
 *
 * MobilePay is branded as Vipps in Denmark, Finland and Greenland from 12 January 2027.
 * The payment method's default title was changed with the rebranding, but a default only
 * applies to a store that has never saved the payment method's settings. A store that has
 * saved them once holds the old title in the database, and would keep offering a brand
 * that no longer exists at checkout, so the stored title is rewritten here.
 *
 * Only a title that is still the old default is rewritten. A merchant who has set a title
 * of their own keeps it.
 */
class VippsTitleMigration {

	/**
	 * The option recording that the migration has run, so it is evaluated only once.
	 */
	const DONE_OPTION = 'nexi_checkout_vipps_title_migrated';

	/**
	 * The settings option of the standalone MobilePay payment method.
	 */
	const SETTINGS_OPTION = 'woocommerce_nets_easy_mobilepay_settings';

	/**
	 * The title the payment method shipped with before the rebranding.
	 */
	const OLD_TITLE = 'MobilePay payment';

	/**
	 * Register the migration.
	 *
	 * It runs on init rather than on plugins_loaded because it compares against a
	 * translated string, which needs the text domain to be available.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'maybe_migrate' ), 20 );
	}

	/**
	 * Rewrite the stored title if it is still the old default.
	 *
	 * On multisite every site holds its own payment method settings, and each one runs
	 * this on its own init, so the sites migrate independently of each other.
	 *
	 * @return void
	 */
	public static function maybe_migrate() {
		if ( get_option( self::DONE_OPTION ) ) {
			return;
		}

		$settings = get_option( self::SETTINGS_OPTION );

		if ( is_array( $settings ) && isset( $settings['title'] ) && self::is_old_default( $settings['title'] ) ) {
			$settings['title'] = __( 'Vipps payment', 'dibs-easy-for-woocommerce' );
			update_option( self::SETTINGS_OPTION, $settings );
		}

		// Recorded whatever the outcome, since a store with nothing to rewrite should not
		// be asked the same question again on every request.
		update_option( self::DONE_OPTION, 'yes' );
	}

	/**
	 * Whether a stored title is still the one the payment method shipped with.
	 *
	 * @param mixed $title The stored title.
	 *
	 * @return bool
	 */
	private static function is_old_default( $title ) {
		if ( ! is_string( $title ) ) {
			return false;
		}

		$title = trim( $title );

		if ( self::OLD_TITLE === $title ) {
			return true;
		}

		/*
		 * The default was passed through __() before it was saved, so on a translated store
		 * the stored title is the translation of it rather than the English string. The
		 * payment method no longer uses this string, and it is written out in full here
		 * only so the translation of it can still be looked up for the comparison.
		 */
		return __( 'MobilePay payment', 'dibs-easy-for-woocommerce' ) === $title;
	}
}
