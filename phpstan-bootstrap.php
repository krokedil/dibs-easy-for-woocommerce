<?php
/**
 * Constant declarations for PHPStan static analysis only.
 *
 * Not loaded at runtime — the real values are defined in dibs-easy-for-woocommerce.php.
 * These declarations exist so PHPStan knows the constants exist when analyzing files
 * that are pulled in via dynamic include_once and therefore can't be traced statically.
 *
 * @package WC_Dibs_Easy
 *
 * @phpcs:disable
 */

define( 'WC_DIBS_EASY_VERSION', '0.0.0' );
define( 'WC_DIBS__URL', 'https://example.com' );
define( 'WC_DIBS_PATH', __DIR__ );
define( 'DIBS_API_LIVE_ENDPOINT', 'https://api.dibspayment.eu/v1/' );
define( 'DIBS_API_TEST_ENDPOINT', 'https://test.api.dibspayment.eu/v1/' );

// Missing constants that are set in WordPress but not in their stubs.
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

// Missing constants that are set in WooCommerce but not in their stubs.
define( 'WOOCOMMERCE_VERSION', '0.0.0' );
