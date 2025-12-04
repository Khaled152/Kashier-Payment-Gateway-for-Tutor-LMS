<?php
/**
 * Kashier Tutor Gateway Class
 *
 * Handles registration of Kashier payment methods with Tutor LMS.
 *
 * @package KashierTutorGateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main gateway class for Kashier Tutor integration.
 */
class Kashier_Tutor_Gateway {

	/**
	 * Singleton instance.
	 *
	 * @var Kashier_Tutor_Gateway
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Kashier_Tutor_Gateway
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
		// Register payment gateways with Tutor LMS.
		add_filter( 'tutor_payment_gateways', array( $this, 'register_payment_gateways' ) );
		
		// Register gateway class references for payment processing.
		add_filter( 'tutor_payment_gateways_with_class', array( $this, 'register_gateway_classes' ) );
		add_filter( 'tutor_gateways_with_class', array( $this, 'register_gateway_classes' ), 10, 2 );
		
		// Handle payment labels.
		add_filter( 'tutor_payment_method_labels', array( $this, 'add_payment_labels' ) );
		
		// Add scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
	}

	/**
	 * Register Kashier payment gateways with Tutor LMS.
	 *
	 * @param array $gateways Existing gateways.
	 * @return array
	 */
	public function register_payment_gateways( $gateways ) {
		$payment_methods = Kashier_Tutor_Helper::get_all_payment_methods();

		foreach ( $payment_methods as $key => $method ) {
			$gateways[] = array(
				'name'                 => $method['name'],
				'label'                => $method['label'],
				'is_installed'         => true,
				'is_plugin_active'     => true,
				'is_active'            => false,
				'icon'                 => Kashier_Tutor_Helper::get_icon_url( $method['icon'] ),
				'support_subscription' => $method['support_subscription'],
				'fields'               => $this->get_gateway_fields( $key ),
			);
		}

		return $gateways;
	}

	/**
	 * Get configuration fields for gateway.
	 *
	 * @param string $gateway_key Gateway key.
	 * @return array
	 */
	private function get_gateway_fields( $gateway_key ) {
		// All Kashier methods share the same credentials.
		// We show full config only for the card method.
		$is_primary = ( 'kashier_card' === $gateway_key );

		$fields = array();

		if ( $is_primary ) {
			$fields = array(
				array(
					'name'    => 'environment',
					'label'   => __( 'Environment', 'kashier-tutor-gateway' ),
					'type'    => 'select',
					'options' => array(
						'test' => __( 'Test (Sandbox)', 'kashier-tutor-gateway' ),
						'live' => __( 'Live', 'kashier-tutor-gateway' ),
					),
					'value'   => 'test',
				),
				array(
					'name'  => 'merchant_id',
					'label' => __( 'Merchant ID', 'kashier-tutor-gateway' ),
					'type'  => 'text',
					'value' => '',
				),
				array(
					'name'  => 'test_api_key',
					'label' => __( 'Test API Key', 'kashier-tutor-gateway' ),
					'type'  => 'secret_key',
					'value' => '',
				),
				array(
					'name'  => 'api_key',
					'label' => __( 'Live API Key', 'kashier-tutor-gateway' ),
					'type'  => 'secret_key',
					'value' => '',
				),
				array(
					'name'  => 'test_secret_key',
					'label' => __( 'Test Secret Key', 'kashier-tutor-gateway' ),
					'type'  => 'secret_key',
					'value' => '',
				),
				array(
					'name'  => 'secret_key',
					'label' => __( 'Live Secret Key', 'kashier-tutor-gateway' ),
					'type'  => 'secret_key',
					'value' => '',
				),
				array(
					'name'  => 'webhook_url',
					'label' => __( 'Webhook URL', 'kashier-tutor-gateway' ),
					'type'  => 'webhook_url',
					'value' => rest_url( 'tutor/v1/ecommerce-webhook/kashier_card' ),
				),
			);
		} else {
			// For other methods, just show a note that credentials are shared.
			$fields = array(
				array(
					'name'  => 'shared_credentials_note',
					'label' => __( 'Credentials', 'kashier-tutor-gateway' ),
					'type'  => 'text',
					'value' => '',
					'desc'  => __( 'This payment method uses the same credentials as Card. Please configure Card payment method first.', 'kashier-tutor-gateway' ),
				),
				array(
					'name'  => 'webhook_url',
					'label' => __( 'Webhook URL', 'kashier-tutor-gateway' ),
					'type'  => 'webhook_url',
					'value' => rest_url( 'tutor/v1/ecommerce-webhook/' . $gateway_key ),
				),
			);
		}

		return $fields;
	}

	/**
	 * Register gateway classes for payment processing.
	 *
	 * @param array       $gateways Existing gateway classes.
	 * @param string|null $payment_method Current payment method.
	 * @return array
	 */
	public function register_gateway_classes( $gateways, $payment_method = null ) {
		$payment_methods = Kashier_Tutor_Helper::get_all_payment_methods();

		foreach ( $payment_methods as $key => $method ) {
			// Create a unique gateway class name for each method.
			$class_name = 'Kashier_Tutor_Gateway_' . ucfirst( str_replace( 'kashier_', '', $key ) );
			
			// Register the class dynamically if not exists.
			if ( ! class_exists( $class_name ) ) {
				$this->create_gateway_class( $class_name, $method['method'] );
			}

			$gateways[ $key ] = array(
				'gateway_class' => $class_name,
				'config_class'  => 'Kashier_Tutor_Config',
			);
		}

		return $gateways;
	}

	/**
	 * Dynamically create a gateway class for a payment method.
	 *
	 * @param string $class_name Class name to create.
	 * @param string $method_code Payment method code.
	 */
	private function create_gateway_class( $class_name, $method_code ) {
		// Use eval to create dynamic class (safe as we control the input).
		$code = "
		class {$class_name} extends Kashier_Tutor_Payment_Gateway {
			public function __construct() {
				parent::__construct( '{$method_code}' );
			}
		}";
		eval( $code ); // phpcs:ignore
	}

	/**
	 * Add payment method labels.
	 *
	 * @param array $labels Existing labels.
	 * @return array
	 */
	public function add_payment_labels( $labels ) {
		$payment_methods = Kashier_Tutor_Helper::get_all_payment_methods();

		foreach ( $payment_methods as $key => $method ) {
			$labels[ $key ] = $method['label'] . ' (Kashier)';
		}

		return $labels;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only on Tutor settings page.
		if ( strpos( $hook, 'tutor' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'kashier-tutor-admin',
			KASHIER_TUTOR_URL . 'assets/css/admin.css',
			array(),
			KASHIER_TUTOR_VERSION
		);
	}

}

