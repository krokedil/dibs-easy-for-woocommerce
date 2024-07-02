<?php
/**
 * Main request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main request class
 */
abstract class Nets_Easy_Request {
	/**
	 * The request method.
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * The request title.
	 *
	 * @var string
	 */
	protected $log_title;

	/**
	 * $key. Nets API request key.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * $endpoint. Nets API endpoint.
	 *
	 * @var string
	 */
	public $endpoint;

	/**
	 * The request arguments.
	 *
	 * @var array
	 */
	protected $arguments;

	/**
	 * The plugin settings.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Whether the plugin is in test mode.
	 *
	 * @var string
	 */
	protected $test_mode;

	/**
	 * Transaction ID
	 *
	 * @var ?string
	 */
	public $payment_id;

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments = array() ) {
		$this->arguments = $arguments;
		$this->load_settings();
		$this->endpoint = $this->get_endpoint();
		$this->key      = $this->get_key();
	}

	/**
	 * Loads the Nexi Checkout settings and sets them to be used here.
	 *
	 * @return void
	 */
	protected function load_settings() {
		$this->settings = get_option( 'woocommerce_dibs_easy_settings' );
	}

	/**
	 * Is testmode enabled.
	 *
	 * @return bool
	 */
	protected function is_test_mode() {
		return 'yes' === $this->settings['test_mode'];
	}

	/**
	 * Get the Dibs endpoint.
	 *
	 * @return string
	 */
	protected function get_endpoint() {
		if ( $this->is_test_mode() ) {
			return DIBS_API_TEST_ENDPOINT;
		}
		return DIBS_API_LIVE_ENDPOINT;
	}

	/**
	 * Get the dibs key.
	 *
	 * @return string
	 */
	protected function get_key() {
		if ( $this->is_test_mode() ) {
			return $this->settings['dibs_test_key'];
		}
		return $this->settings['dibs_live_key'];
	}

	/**
	 * Get the request headers.
	 *
	 * @param number|null $order_id The WooCommerce order id.
	 * @return array
	 */
	protected function get_request_headers( $order_id = null ) {
		return array(
			'Content-type'        => 'application/json',
			'Accept'              => 'application/json',
			'Authorization'       => $this->calculate_auth( $order_id ),
			'commercePlatformTag' => 'WooEasyKrokedil',
		);
	}

	/**
	 * Get the user agent.
	 *
	 * @return string
	 */
	protected function get_user_agent() {
		$protocols = array( 'http://', 'http://www.', 'https://', 'https://www.' );
		$url       = str_replace( $protocols, '', get_bloginfo( 'url' ) );
		return apply_filters( 'dibs_easy_http_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . iconv( 'UTF-8', 'ASCII//IGNORE', $url ) ) . ' - Plugin/' . WC_DIBS_EASY_VERSION . ' - PHP/' . PHP_VERSION . ' - Krokedil';
	}

	/**
	 * Calculates the basic auth.
	 *
	 * @param number|null $order_id The WooCommerce order id.
	 * @return string
	 */
	protected function calculate_auth( $order_id = null ) {
		return apply_filters( 'dibs_easy_request_secret_key', $this->key, $this->is_test_mode(), $order_id );
	}

	/**
	 * Get the request args.
	 *
	 * @return array
	 */
	abstract protected function get_request_args();

	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	abstract protected function get_request_url();

	/**
	 * Make the request.
	 *
	 * @return object|WP_Error
	 */
	public function request() {

		$url      = $this->get_request_url();
		$args     = $this->get_request_args();
		$response = wp_remote_request( $url, $args );
		return $this->process_response( $response, $args, $url );
	}

	/**
	 * Processes the response checking for errors.
	 *
	 * @param object|WP_Error $response The response from the request.
	 * @param array           $request_args The request args.
	 * @param string          $request_url The request url.
	 * @return array|WP_Error
	 */
	protected function process_response( $response, $request_args, $request_url ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code > 299 ) {
			$data          = 'URL: ' . $request_url . ' - ' . wp_json_encode( $request_args );
			$error_message = '';
			// Get the error messages.
			if ( null !== json_decode( $response['body'], true ) ) {
				$errors = json_decode( $response['body'], true );
				foreach ( $errors as $properties ) {
					if ( is_array( $properties ) ) {
						foreach ( $properties as $property ) {
							foreach ( $property as $err_message ) {
								$error_message .= ' ' . $err_message;
							}
						}
					} else {
						$error_message .= ' ' . $properties;
					}
				}
			} else {
				$message       = wp_remote_retrieve_response_message( $response );
				$error_message = "API Error {$response_code}, message : {$message}";
			}
			$return = new WP_Error( $response_code, $error_message, $data );
		} else {
			$return = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		$this->log_response( $response, $request_args, $request_url );
		return $return;
	}

	/**
	 * Formats error message.
	 *
	 * @param mixed $response WC order id.
	 * @return object
	 */
	public function get_error_message( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );
		if ( empty( $response_body ) ) {
			$response_body = 'Response code ' . $response['response']['code'] . '. Message: ' . $response['response']['message'];
		}

		$errors = new WP_Error();
		$errors->add( 'dibs_easy', $response_body );

		Nets_Easy_Logger::log( 'DIBS Error Response: ' . stripslashes_deep( wp_json_encode( $response_body ) ) );
		return $errors;
	}

	/**
	 * Logs the response from the request.
	 *
	 * @param array|WP_Error $response The response from the request.
	 * @param array          $request_args The request args.
	 * @param string         $request_url The request URL.
	 *
	 * @return void
	 */
	protected function log_response( $response, $request_args, $request_url ) {
		$method = $this->method;
		$title  = $this->log_title;
		$code   = wp_remote_retrieve_response_code( $response );

		$body = json_decode( $response['body'], true );

		// Set payment id for reference iin log.
		if ( ! empty( $this->payment_id ) ) {
			$order_id = $this->payment_id;
		} else {
			$order_id = $body['paymentId'] ?? $body['payment']['paymentId'] ?? null;
		}

		$log = Nets_Easy_Logger::format_log( $order_id, $method, $title, $request_args, $request_url, $response, $code );
		Nets_Easy_Logger::log( $log );
	}
}
