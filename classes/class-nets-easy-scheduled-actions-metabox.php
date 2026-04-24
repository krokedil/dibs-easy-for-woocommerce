<?php
/**
 * Scheduled actions meta box.
 *
 * Displays Action Scheduler jobs related to a Nexi order in a side meta box
 * on the order edit screen (HPOS and legacy).
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nets_Easy_Scheduled_Actions_Metabox class.
 */
class Nets_Easy_Scheduled_Actions_Metabox {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Resolve the order ID being edited, supporting both HPOS and legacy.
	 *
	 * @return int
	 */
	private function get_the_order_id() {
		$order_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin context.
		if ( ! $order_id ) {
			$order_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin context.
		}
		return $order_id;
	}

	/**
	 * Register the meta box on the Nexi order edit screen.
	 *
	 * @param string $post_type The current post type / screen id.
	 * @return void
	 */
	public function register_meta_box( $post_type ) {
		if ( ! in_array( $post_type, array( 'woocommerce_page_wc-orders', 'shop_order' ), true ) ) {
			return;
		}

		$order = wc_get_order( $this->get_the_order_id() );
		if ( ! $order ) {
			return;
		}

		if ( ! in_array( $order->get_payment_method(), nets_easy_all_payment_method_ids(), true ) ) {
			return;
		}

		add_meta_box(
			'nets_easy_scheduled_actions_meta_box',
			__( 'Nexi Checkout', 'dibs-easy-for-woocommerce' ),
			array( $this, 'render_meta_box' ),
			$post_type,
			'side',
			'core'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @return void
	 */
	public function render_meta_box() {
		$order = wc_get_order( $this->get_the_order_id() );
		if ( ! $order ) {
			return;
		}

		$payment_id = $order->get_meta( '_dibs_payment_id' );

		if ( empty( $payment_id ) ) {
			?>
			<p><?php esc_html_e( 'No Nexi payment ID is associated with this order yet.', 'dibs-easy-for-woocommerce' ); ?></p>
			<?php
			return;
		}

		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			?>
			<p><?php esc_html_e( 'Action Scheduler is not available.', 'dibs-easy-for-woocommerce' ); ?></p>
			<?php
			return;
		}

		$date_created       = $order->get_date_created();
		$order_created_date = $date_created ? $date_created->format( 'Y-m-d H:i:s' ) : '';

		self::print_scheduled_actions( $payment_id, $order_created_date );
	}

	/**
	 * Print the scheduled actions summary and a link to the Action Scheduler list filtered by the given search term.
	 *
	 * @param string $search_term        The term to search scheduled actions by (typically the Nexi payment ID).
	 * @param string $order_created_date The order creation date (Y-m-d H:i:s). Used to narrow the query.
	 * @return void
	 */
	public static function print_scheduled_actions( $search_term, $order_created_date = '' ) {
		$counts = self::get_scheduled_action_counts( $search_term, $order_created_date );

		$query_url = admin_url(
			'admin.php?page=wc-status&tab=action-scheduler&s=' . rawurlencode( $search_term ) . '&action=-1&paged=1&action2=-1'
		);
		?>
		<strong>
			<?php esc_html_e( 'Scheduled actions', 'dibs-easy-for-woocommerce' ); ?>
			<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'See all actions scheduled for this order.', 'dibs-easy-for-woocommerce' ); ?>"></span>
		</strong>
		<br />
		<a target="_blank" href="<?php echo esc_url( $query_url ); ?>">
			<?php
			printf(
				/* translators: 1: number of completed actions, 2: number of failed actions, 3: number of pending actions. */
				esc_html__( '%1$d completed, %2$d failed, %3$d pending', 'dibs-easy-for-woocommerce' ),
				esc_html( $counts['complete'] ),
				esc_html( $counts['failed'] ),
				esc_html( $counts['pending'] )
			);
			?>
		</a>
		<?php
	}

	/**
	 * Count scheduled actions for the given search term, narrowed by hook and order-creation date.
	 *
	 * Action Scheduler deletes completed/failed actions after a retention period (default 30 days), so any
	 * order older than a few months has nothing to show. We short-circuit those to avoid unnecessary queries.
	 *
	 * @param string $search_term        The term to search scheduled actions by (typically the Nexi payment ID).
	 * @param string $order_created_date The order creation date (Y-m-d H:i:s). Empty string disables the date filter.
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
new Nets_Easy_Scheduled_Actions_Metabox();
