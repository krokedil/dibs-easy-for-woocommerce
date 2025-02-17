<?php //phpcs:disable

function customize_php_scoper_config( array $config ): array {
    // Ignore the ABSPATH constant when scoping.
	$config['exclude-constants'][] = 'ABSPATH';
	$config['exclude-constants'][] = 'WC_DIBS_EASY_VERSION';
	$config['exclude-constants'][] = 'WC_DIBS__URL';
	$config['exclude-constants'][] = 'WC_DIBS_PATH';
	$config['exclude-constants'][] = 'DIBS_API_LIVE_ENDPOINT';
	$config['exclude-constants'][] = 'DIBS_API_TEST_ENDPOINT';
	$config['exclude-classes'][] = 'WooCommerce';
	$config['exclude-classes'][] = 'WC_Product';
	$config['exclude-classes'][] = 'WP_Error';

	$functions = array(
		'Nets_Easy',
	);

	$config['exclude-functions'] = array_merge( $config['exclude-functions'] ?? array(), $functions );
	$config['exclude-namespaces'][] = 'Automattic';

	return $config;
}
