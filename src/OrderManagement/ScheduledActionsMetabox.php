<?php
/**
 * Scheduled actions meta box.
 *
 * Displays Action Scheduler jobs related to a Nexi order in a side meta box
 * on the order edit screen (HPOS and legacy).
 *
 * @package Nexi/OrderManagement
 */

namespace Krokedil\Nexi\OrderManagement;

use KrokedilNexiCheckoutDeps\Krokedil\WooCommerce\OrderMetabox;

defined( 'ABSPATH' ) || exit;

/**
 * ScheduledActionsMetabox class.
 */
class ScheduledActionsMetabox extends OrderMetabox {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'nets_easy_scheduled_actions',
			__( 'Nexi Checkout', 'dibs-easy-for-woocommerce' ),
			''
		);
	}

	/**
	 * Register the meta box only for orders paid via a Nexi gateway.
	 *
	 * The base class supports a single payment_method_id; this plugin exposes
	 * several gateway slugs (card, Swish, MobilePay, etc.), so we gate it
	 * against the full set before delegating to the base implementation.
	 *
	 * @param string $post_type The current post type / screen id.
	 * @return void
	 */
	public function add_metabox( $post_type ) {
		if ( ! $this->is_edit_order_screen( $post_type ) ) {
			return;
		}

		$order_id = $this->get_id();
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order ) {
			return;
		}

		if ( ! in_array( $order->get_payment_method(), nets_easy_all_payment_method_ids(), true ) ) {
			return;
		}

		parent::add_metabox( $post_type );
	}

	/**
	 * Render the meta box body.
	 *
	 * @param \WP_Post|\WC_Order $post The post or order object.
	 * @return void
	 */
	public function metabox_content( $post ) {
		$order = is_a( $post, \WC_Order::class ) ? $post : wc_get_order( $this->get_id() );
		if ( ! $order ) {
			return;
		}

		$payment_id = $order->get_meta( '_dibs_payment_id' );
		if ( empty( $payment_id ) ) {
			self::output_error( __( 'No Nexi payment ID is associated with this order yet.', 'dibs-easy-for-woocommerce' ) );
			return;
		}

		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			self::output_error( __( 'Action Scheduler is not available.', 'dibs-easy-for-woocommerce' ) );
			return;
		}

		$date_created       = $order->get_date_created();
		$order_created_date = $date_created ? $date_created->format( 'Y-m-d H:i:s' ) : '';

		$counts    = self::get_scheduled_action_counts( $payment_id, $order_created_date );
		$query_url = admin_url(
			'admin.php?page=wc-status&tab=action-scheduler&s=' . rawurlencode( $payment_id ) . '&action=-1&paged=1&action2=-1'
		);

		$link_text = sprintf(
			/* translators: 1: number of completed actions, 2: number of failed actions, 3: number of pending actions. */
			__( '%1$d completed, %2$d failed, %3$d pending', 'dibs-easy-for-woocommerce' ),
			$counts['complete'],
			$counts['failed'],
			$counts['pending']
		);

		self::output_info(
			__( 'Scheduled actions', 'dibs-easy-for-woocommerce' ),
			'<a target="_blank" href="' . esc_url( $query_url ) . '">' . esc_html( $link_text ) . '</a>',
			__( 'See all actions scheduled for this order.', 'dibs-easy-for-woocommerce' )
		);
	}

	/**
	 * Count scheduled actions for the given search term, narrowed by hook and order-creation date.
	 *
	 * Action Scheduler prunes completed/failed actions after a retention period (default 30 days),
	 * so anything older than a few months has nothing to show — short-circuit those.
	 *
	 * @param string $search_term        The term to search scheduled actions by (the Nexi payment ID).
	 * @param string $order_created_date The order creation date (Y-m-d H:i:s). Empty disables the date filter.
	 * @return array<string,int> Counts keyed by status ('complete', 'failed', 'pending').
	 */
	private static function get_scheduled_action_counts( $search_term, $order_created_date ) {
		$counts = array(
			'complete' => 0,
			'failed'   => 0,
			'pending'  => 0,
		);

		if ( ! empty( $order_created_date ) ) {
			$order_created_ts = strtotime( $order_created_date );
			if ( $order_created_ts && $order_created_ts < strtotime( '-3 months' ) ) {
				return $counts;
			}
		}

		$query_args = array(
			'search'   => $search_term,
			'hook'     => 'dibs_payment_created_callback',
			'per_page' => -1,
		);

		if ( ! empty( $order_created_date ) ) {
			$query_args['date']         = $order_created_date;
			$query_args['date_compare'] = '>=';
		}

		foreach ( array_keys( $counts ) as $status ) {
			$query_args['status'] = array( $status );
			$actions              = as_get_scheduled_actions( $query_args, 'ids' );
			$counts[ $status ]    = is_array( $actions ) ? count( $actions ) : 0;
		}

		return $counts;
	}
}
