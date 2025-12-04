<?php
/**
 * Kashier Tutor Helper Class
 *
 * @package KashierTutorGateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * Helper class for Kashier Tutor integration.
 */
class Kashier_Tutor_Helper {

	/**
	 * Payment methods configuration.
	 *
	 * @var array
	 */
	public static $payment_methods = array(
		'kashier_card' => array(
			'name'                 => 'kashier_card',
			'label'                => 'Card',
			'method'               => 'card',
			'description'          => 'Online Payments via Credit Card by Kashier',
			'icon'                 => 'credit-card.svg',
			'support_subscription' => true,
		),
		'kashier_bank_installments' => array(
			'name'                 => 'kashier_bank_installments',
			'label'                => 'Bank Installment',
			'method'               => 'bank_installments',
			'description'          => 'Online Payments via Bank Installment by Kashier',
			'icon'                 => 'bank-installments.svg',
			'support_subscription' => false,
		),
		'kashier_valu' => array(
			'name'                 => 'kashier_valu',
			'label'                => 'ValU',
			'method'               => 'valu',
			'description'          => 'Online Payments via ValU by Kashier',
			'icon'                 => 'valu.svg',
			'support_subscription' => false,
		),
		'kashier_souhoola' => array(
			'name'                 => 'kashier_souhoola',
			'label'                => 'Souhoola',
			'method'               => 'souhoola',
			'description'          => 'Online Payments via Souhoola by Kashier',
			'icon'                 => 'souhoola.svg',
			'support_subscription' => false,
		),
		'kashier_aman' => array(
			'name'                 => 'kashier_aman',
			'label'                => 'Aman',
			'method'               => 'aman',
			'description'          => 'Online Payments via Aman by Kashier',
			'icon'                 => 'aman.svg',
			'support_subscription' => false,
		),
		'kashier_wallet' => array(
			'name'                 => 'kashier_wallet',
			'label'                => 'Mobile Wallet',
			'method'               => 'wallet',
			'description'          => 'Online Payments via Mobile Wallet by Kashier',
			'icon'                 => 'meeza-wallet.svg',
			'support_subscription' => false,
		),
	);

	/**
	 * API endpoints.
	 */
	const API_TEST_URL = 'https://test-api.kashier.io';
	const API_LIVE_URL = 'https://api.kashier.io';
	const IFRAME_BASE_URL = 'https://payments.kashier.io';

	/**
	 * Get payment method config.
	 *
	 * @param string $method_key Method key.
	 * @return array|null
	 */
	public static function get_payment_method( $method_key ) {
		return isset( self::$payment_methods[ $method_key ] ) ? self::$payment_methods[ $method_key ] : null;
	}

	/**
	 * Get all payment methods.
	 *
	 * @return array
	 */
	public static function get_all_payment_methods() {
		return self::$payment_methods;
	}

	/**
	 * Get gateway settings from Tutor.
	 *
	 * @param string $gateway_name Gateway name.
	 * @return array
	 */
	public static function get_gateway_settings( $gateway_name ) {
		// Tutor stores payment settings in 'payment_settings' option key.
		$settings = tutor_utils()->get_option( 'payment_settings' );
		
		if ( is_string( $settings ) ) {
			$settings = json_decode( stripslashes( $settings ), true );
		}

		if ( ! empty( $settings['payment_methods'] ) && is_array( $settings['payment_methods'] ) ) {
			foreach ( $settings['payment_methods'] as $method ) {
				if ( isset( $method['name'] ) && $method['name'] === $gateway_name ) {
					return $method;
				}
			}
		}

		return array();
	}

	/**
	 * Get Kashier merchant ID.
	 *
	 * @return string
	 */
	public static function get_merchant_id() {
		$settings = self::get_gateway_settings( 'kashier_card' );
		return isset( $settings['merchant_id'] ) ? $settings['merchant_id'] : '';
	}

	/**
	 * Get API key based on mode.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		$settings = self::get_gateway_settings( 'kashier_card' );
		$is_test  = isset( $settings['environment'] ) && 'test' === $settings['environment'];
		
		if ( $is_test ) {
			return isset( $settings['test_api_key'] ) ? $settings['test_api_key'] : '';
		}
		
		return isset( $settings['api_key'] ) ? $settings['api_key'] : '';
	}

	/**
	 * Get secret key based on mode.
	 *
	 * @return string
	 */
	public static function get_secret_key() {
		$settings = self::get_gateway_settings( 'kashier_card' );
		$is_test  = isset( $settings['environment'] ) && 'test' === $settings['environment'];
		
		if ( $is_test ) {
			return isset( $settings['test_secret_key'] ) ? $settings['test_secret_key'] : '';
		}
		
		return isset( $settings['secret_key'] ) ? $settings['secret_key'] : '';
	}

	/**
	 * Check if test mode is enabled.
	 *
	 * @return bool
	 */
	public static function is_test_mode() {
		$settings = self::get_gateway_settings( 'kashier_card' );
		return isset( $settings['environment'] ) && 'test' === $settings['environment'];
	}

	/**
	 * Get API URL based on mode.
	 *
	 * @return string
	 */
	public static function get_api_url() {
		return self::is_test_mode() ? self::API_TEST_URL : self::API_LIVE_URL;
	}

	/**
	 * Generate payment hash.
	 *
	 * @param string $merchant_id Merchant ID.
	 * @param string $order_id Order ID.
	 * @param float  $amount Amount.
	 * @param string $currency Currency.
	 * @param string $api_key API Key.
	 * @return string
	 */
	public static function generate_hash( $merchant_id, $order_id, $amount, $currency, $api_key ) {
		$path = '/?payment=' . $merchant_id . '.' . $order_id . '.' . $amount . '.' . $currency;
		return hash_hmac( 'sha256', $path, $api_key, false );
	}

	/**
	 * Validate webhook signature.
	 *
	 * @param array  $data Signature data.
	 * @param string $signature Received signature.
	 * @param string $api_key API Key.
	 * @return bool
	 */
	public static function validate_signature( $data, $signature, $api_key ) {
		$query_string = http_build_query( $data, '', '&', PHP_QUERY_RFC3986 );
		$calculated   = hash_hmac( 'sha256', $query_string, $api_key, false );
		return $calculated === $signature;
	}

	/**
	 * Get icon URL for payment method.
	 *
	 * @param string $icon Icon filename.
	 * @return string
	 */
	public static function get_icon_url( $icon ) {
		// Try to get from Kashier WooCommerce plugin if available.
		$kashier_wc_path = WP_PLUGIN_DIR . '/Kashier-WooCommerce-Plugin-master/assets/images/' . $icon;
		if ( file_exists( $kashier_wc_path ) ) {
			return plugins_url( 'Kashier-WooCommerce-Plugin-master/assets/images/' . $icon );
		}

		// Fallback to our plugin.
		return KASHIER_TUTOR_URL . 'assets/images/' . $icon;
	}

	/**
	 * Log message if debugging is enabled.
	 *
	 * @param string $message Message to log.
	 * @param string $level Log level.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Kashier Tutor Gateway][' . strtoupper( $level ) . '] ' . $message ); // phpcs:ignore
		}
	}

	/**
	 * URL encode component (JavaScript compatible).
	 *
	 * @param string $str String to encode.
	 * @return string
	 */
	public static function encode_url_component( $str ) {
		$revert = array(
			'%21' => '!',
			'%2A' => '*',
			'%27' => "'",
			'%28' => '(',
			'%29' => ')',
		);
		return strtr( rawurlencode( $str ), $revert );
	}

	/**
	 * Check if gateway is configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		$merchant_id = self::get_merchant_id();
		$api_key     = self::get_api_key();
		$secret_key  = self::get_secret_key();

		return ! empty( $merchant_id ) && ! empty( $api_key ) && ! empty( $secret_key );
	}
}

