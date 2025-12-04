<?php
/**
 * Kashier Tutor Webhook Handler
 *
 * Handles webhook callbacks from Kashier for payment status updates.
 *
 * @package KashierTutorGateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * Webhook handler class.
 */
class Kashier_Tutor_Webhook {

	/**
	 * Singleton instance.
	 *
	 * @var Kashier_Tutor_Webhook
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Kashier_Tutor_Webhook
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Handle enrollment after successful payment.
		add_action( 'tutor_order_payment_updated', array( $this, 'handle_enrollment' ), 10, 1 );
	}

	/**
	 * Handle enrollment after successful payment.
	 *
	 * @param object $order_data Order data with payment status.
	 */
	public function handle_enrollment( $order_data ) {
		// Only process Kashier payments.
		if ( ! isset( $order_data->id ) ) {
			return;
		}

		global $wpdb;
		
		// Get order to check payment method.
		$order = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT payment_method FROM {$wpdb->prefix}tutor_orders WHERE id = %d",
				$order_data->id
			)
		);

		if ( ! $order ) {
			return;
		}

		// Check if it's a Kashier payment.
		$kashier_methods = array_keys( Kashier_Tutor_Helper::get_all_payment_methods() );
		if ( ! in_array( $order->payment_method, $kashier_methods, true ) ) {
			return;
		}

		// Payment is already handled by the gateway class.
		Kashier_Tutor_Helper::log( 'Enrollment handler triggered for order #' . $order_data->id );
	}
}

