<?php
/**
 * Express Button feature bootstrapper.
 *
 * @package Krokedil\NexiCheckout\ExpressButton
 */

namespace Krokedil\Nexi\ExpressButton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstraps the Express Button feature: enqueues assets and registers AJAX handlers.
 */
class ExpressButton {

	/**
	 * Whether the express button is enabled.
	 *
	 * @var bool
	 */
	private bool $enabled;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings      = get_option( 'woocommerce_dibs_easy_settings', [] );
		$this->enabled = 'yes' === ( $settings['express_button_enabled'] ?? 'no' );

		if ( ! $this->enabled ) {
			return;
		}

		new Assets();
		new Ajax();
	}
}
