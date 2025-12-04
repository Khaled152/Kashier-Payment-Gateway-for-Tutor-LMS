<?php
/**
 * Kashier Payment Gateway for Tutor LMS
 *
 * Extends GatewayBase to integrate Kashier payments with Tutor LMS.
 *
 * @package KashierTutorGateway
 */

defined( 'ABSPATH' ) || exit;

use Tutor\PaymentGateways\GatewayBase;

/**
 * Kashier Payment Gateway class that integrates with Tutor LMS.
 */
class Kashier_Tutor_Payment_Gateway extends GatewayBase {

	/**
	 * Payment method code.
	 *
	 * @var string
	 */
	private $method_code = 'card';

	/**
	 * Gateway name.
	 *
	 * @var string
	 */
	private $gateway_name = 'kashier_card';

	/**
	 * Constructor.
	 *
	 * @param string $method_code Payment method code (e.g., 'card', 'wallet', 'valu').
	 */
	public function __construct( $method_code = 'card' ) {
		$this->method_code = $method_code;
		
		// Handle method codes that might have underscores.
		$method_key = str_replace( '_', '-', $method_code );
		if ( 'bank-installments' === $method_key ) {
			$this->gateway_name = 'kashier_bank_installments';
		} else {
			$this->gateway_name = 'kashier_' . str_replace( '-', '_', $method_code );
		}

		// Note: We intentionally don't call parent::__construct() 
		// because Kashier doesn't use PaymentHub.
		// We handle payments via redirect to Kashier's hosted payment page.
	}

	/**
	 * Get root directory name.
	 *
	 * @return string
	 */
	public function get_root_dir_name(): string {
		return 'Kashier';
	}

	/**
	 * Get payment class (not used for Kashier).
	 *
	 * @return string
	 */
	public function get_payment_class(): string {
		return '';
	}

	/**
	 * Get config class.
	 *
	 * @return string
	 */
	public function get_config_class(): string {
		return 'Kashier_Tutor_Config';
	}

	/**
	 * Get autoload file (not needed for Kashier).
	 *
	 * @return string
	 */
	public static function get_autoload_file() {
		return '';
	}

	/**
	 * Setup payment and redirect to Kashier.
	 *
	 * @param mixed $data Payment data from Tutor.
	 *
	 * @throws \Exception If payment fails.
	 */
	public function setup_payment_and_redirect( $data ) {
		// Check if configured.
		if ( ! Kashier_Tutor_Helper::is_configured() ) {
			throw new \Exception(
				__( 'Kashier payment gateway is not configured properly. Please contact the administrator.', 'kashier-tutor-gateway' )
			);
		}

		$method_config = Kashier_Tutor_Helper::get_payment_method( $this->gateway_name );
		if ( ! $method_config ) {
			throw new \Exception( __( 'Invalid payment method.', 'kashier-tutor-gateway' ) );
		}

		// Get payment details from Tutor's data object.
		$order_id = $data->order_id;
		$amount   = floatval( $data->total_amount ?? $data->sub_total_amount ?? 0 );
		$currency = isset( $data->currency->code ) ? $data->currency->code : 'EGP';

		// Get Kashier settings.
		$merchant_id = Kashier_Tutor_Helper::get_merchant_id();
		$api_key     = Kashier_Tutor_Helper::get_api_key();
		$mode        = Kashier_Tutor_Helper::is_test_mode() ? 'test' : 'live';

		// Generate unique order ID with timestamp to avoid duplicates.
		$kashier_order_id = $order_id . '-' . time();

		// Save Kashier order ID to database.
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'tutor_orders',
			array( 'transaction_id' => $kashier_order_id ),
			array( 'id' => $order_id ),
			array( '%s' ),
			array( '%d' )
		);

		// Generate hash.
		$hash = Kashier_Tutor_Helper::generate_hash(
			$merchant_id,
			$kashier_order_id,
			$amount,
			$currency,
			$api_key
		);

		// Get customer details.
		$customer_email = '';
		$customer_name  = '';

		if ( isset( $data->customer ) ) {
			$customer_email = $data->customer->email ?? '';
			$first_name     = $data->customer->first_name ?? '';
			$last_name      = $data->customer->last_name ?? '';
			$customer_name  = trim( $first_name . ' ' . $last_name );
		}

		if ( empty( $customer_email ) ) {
			$user = wp_get_current_user();
			$customer_email = $user->user_email;
			$customer_name  = trim( $user->first_name . ' ' . $user->last_name );
			if ( empty( $customer_name ) ) {
				$customer_name = $user->display_name;
			}
		}

		// Clean customer name for URL.
		$customer_name = str_replace( ' ', '-', $customer_name );

		// Prepare metadata.
		$metadata = Kashier_Tutor_Helper::encode_url_component(
			wp_json_encode(
				array(
					'ecommercePlatform' => 'tutor-lms',
					'OrderId'           => $order_id,
					'CustomerEmail'     => $customer_email,
					'CustomerName'      => $customer_name,
				)
			)
		);

		// Get language.
		$language = substr( get_bloginfo( 'language' ), 0, 2 );
		$language = ( 'ar' === $language ) ? 'ar' : 'en';

		// Prepare Kashier method code.
		$kashier_method  = $method_config['method'];
		$allowed_methods = $kashier_method;

		// Handle BNPL methods (ValU, Souhoola, Aman).
		if ( in_array( $kashier_method, array( 'valu', 'souhoola', 'aman' ), true ) ) {
			$allowed_methods = 'bnpl[' . $kashier_method . ']';
		}

		// Build URLs.
		$success_url = add_query_arg(
			array(
				'tutor_order_placement' => 'success',
				'order_id'              => $order_id,
			),
			home_url()
		);

		$failure_url = add_query_arg(
			array(
				'tutor_order_placement' => 'failed',
				'order_id'              => $order_id,
			),
			home_url()
		);

		$webhook_url = rest_url( 'tutor/v1/ecommerce-webhook/' . $this->gateway_name );

		// Build Kashier payment URL.
		$query_params = array(
			'merchantId'       => $merchant_id,
			'orderId'          => $kashier_order_id,
			'amount'           => $amount,
			'currency'         => $currency,
			'hash'             => $hash,
			'mode'             => $mode,
			'metaData'         => $metadata,
			'merchantRedirect' => $success_url,
			'failureRedirect'  => 'true',
			'redirectMethod'   => 'get',
			'display'          => $language,
			'serverWebhook'    => $webhook_url,
			'allowedMethods'   => $allowed_methods,
		);

		// Add default method for BNPL.
		if ( in_array( $kashier_method, array( 'valu', 'souhoola', 'aman' ), true ) ) {
			$query_params['defaultMethod'] = 'bnpl[' . $kashier_method . ']';
		}

		$payment_url = Kashier_Tutor_Helper::IFRAME_BASE_URL . '?' . http_build_query( $query_params );

		Kashier_Tutor_Helper::log( 'Redirecting to Kashier for order #' . $order_id );

		// Redirect to Kashier.
		wp_redirect( $payment_url );
		exit;
	}

	/**
	 * Verify webhook signature and return order data.
	 *
	 * @param object $webhook_data Webhook data.
	 * @return object|null
	 */
	public function verify_webhook_signature( $webhook_data ) {
		Kashier_Tutor_Helper::log( 'Webhook verification started' );

		// Handle POST webhook (from Kashier server).
		if ( ! empty( $webhook_data->stream ) ) {
			return $this->handle_post_webhook( $webhook_data );
		}

		// Handle GET redirect (from customer browser).
		if ( ! empty( $webhook_data->get ) ) {
			return $this->handle_get_redirect( $webhook_data->get );
		}

		return null;
	}

	/**
	 * Handle POST webhook from Kashier.
	 *
	 * @param object $webhook_data Webhook data.
	 * @return object|null
	 */
	private function handle_post_webhook( $webhook_data ) {
		$json_data = json_decode( $webhook_data->stream, true );

		if ( ! $json_data || ! isset( $json_data['data'] ) ) {
			Kashier_Tutor_Helper::log( 'Invalid webhook data', 'error' );
			return null;
		}

		$data  = $json_data['data'];
		$event = isset( $json_data['event'] ) ? $json_data['event'] : '';

		// Get order ID.
		$merchant_order_id = isset( $data['merchantOrderId'] ) ? $data['merchantOrderId'] : '';
		$order_id          = $this->extract_order_id( $merchant_order_id );

		if ( ! $order_id ) {
			Kashier_Tutor_Helper::log( 'Order ID not found in webhook', 'error' );
			return null;
		}

		// Verify signature if present.
		$signature = isset( $webhook_data->server['HTTP_X_KASHIER_SIGNATURE'] ) 
			? $webhook_data->server['HTTP_X_KASHIER_SIGNATURE'] 
			: '';

		if ( ! empty( $signature ) && isset( $data['signatureKeys'] ) ) {
			$signature_data = array();
			foreach ( $data['signatureKeys'] as $key ) {
				if ( isset( $data[ $key ] ) ) {
					$signature_data[ $key ] = $data[ $key ];
				}
			}

			$api_key = Kashier_Tutor_Helper::get_api_key();
			if ( ! Kashier_Tutor_Helper::validate_signature( $signature_data, $signature, $api_key ) ) {
				Kashier_Tutor_Helper::log( 'Invalid signature', 'error' );
				return null;
			}
		}

		// Process payment event.
		if ( 'pay' === $event ) {
			$status = isset( $data['status'] ) ? strtoupper( $data['status'] ) : '';

			if ( 'SUCCESS' === $status ) {
				$transaction_id = isset( $data['transactionId'] ) ? $data['transactionId'] : '';
				$kashier_order_id = isset( $data['kashierOrderId'] ) ? $data['kashierOrderId'] : '';

				// Update order with transaction details.
				global $wpdb;
				$wpdb->update(
					$wpdb->prefix . 'tutor_orders',
					array( 'transaction_id' => $transaction_id ),
					array( 'id' => $order_id ),
					array( '%s' ),
					array( '%d' )
				);

				// Save Kashier order ID.
				update_post_meta( $order_id, '_kashier_order_id', $kashier_order_id );

				Kashier_Tutor_Helper::log( 'Payment successful for order #' . $order_id );

				return (object) array(
					'id'             => $order_id,
					'payment_status' => 'paid',
					'transaction_id' => $transaction_id,
				);
			}
		}

		return null;
	}

	/**
	 * Handle GET redirect from Kashier.
	 *
	 * @param array $params GET parameters.
	 * @return object|null
	 */
	private function handle_get_redirect( $params ) {
		$merchant_order_id = isset( $params['merchantOrderId'] ) ? $params['merchantOrderId'] : '';
		$order_id          = $this->extract_order_id( $merchant_order_id );
		$status            = isset( $params['paymentStatus'] ) ? strtoupper( $params['paymentStatus'] ) : '';

		if ( ! $order_id ) {
			return null;
		}

		$redirect_url = '';

		if ( 'SUCCESS' === $status ) {
			$transaction_id = isset( $params['transactionId'] ) ? $params['transactionId'] : '';

			// Update order.
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'tutor_orders',
				array( 'transaction_id' => $transaction_id ),
				array( 'id' => $order_id ),
				array( '%s' ),
				array( '%d' )
			);

			Kashier_Tutor_Helper::log( 'Payment redirect successful for order #' . $order_id );

			$redirect_url = add_query_arg(
				array(
					'tutor_order_placement' => 'success',
					'order_id'              => $order_id,
				),
				home_url()
			);

			return (object) array(
				'id'             => $order_id,
				'payment_status' => 'paid',
				'transaction_id' => $transaction_id,
				'redirectUrl'    => $redirect_url,
			);
		}

		$redirect_url = add_query_arg(
			array(
				'tutor_order_placement' => 'failed',
				'order_id'              => $order_id,
			),
			home_url()
		);

		return (object) array(
			'id'          => $order_id,
			'redirectUrl' => $redirect_url,
		);
	}

	/**
	 * Extract original order ID from Kashier order ID.
	 *
	 * @param string $merchant_order_id Kashier order ID format: {order_id}-{timestamp}.
	 * @return int|false
	 */
	private function extract_order_id( $merchant_order_id ) {
		if ( empty( $merchant_order_id ) ) {
			return false;
		}

		$parts = explode( '-', $merchant_order_id );

		if ( count( $parts ) >= 2 ) {
			$order_id = intval( $parts[0] );

			// Verify order exists.
			global $wpdb;
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}tutor_orders WHERE id = %d",
					$order_id
				)
			);

			return $exists ? $order_id : false;
		}

		return false;
	}
}

/**
 * Kashier Configuration class.
 */
class Kashier_Tutor_Config {

	/**
	 * Gateway name.
	 *
	 * @var string
	 */
	protected $name = 'kashier_card';

	/**
	 * Constructor.
	 *
	 * @param string $gateway_name Gateway name.
	 */
	public function __construct( $gateway_name = 'kashier_card' ) {
		$this->name = $gateway_name;
	}

	/**
	 * Check if gateway is configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return Kashier_Tutor_Helper::is_configured();
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}

