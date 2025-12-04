<?php
/**
 * Plugin Name: Kashier Payment Gateway for Tutor LMS
 * Author: Khaled Heakal
 * Author URI: https://github.com/Khaled152
 * Version: 1.0.0
 * Requires at least: 5.3
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Text Domain: kashier-tutor-gateway
 * Domain Path: /languages
 * License: GPLv2 or later
 *
 * @package KashierTutorGateway
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'KASHIER_TUTOR_VERSION', '1.0.0' );
define( 'KASHIER_TUTOR_FILE', __FILE__ );
define( 'KASHIER_TUTOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'KASHIER_TUTOR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if Tutor LMS is active and using native eCommerce.
 *
 * @return bool
 */
function kashier_tutor_check_requirements() {
	if ( ! class_exists( 'TUTOR\Tutor' ) ) {
		return false;
	}

	$monetize_by = tutor_utils()->get_option( 'monetize_by' );
	return 'tutor' === $monetize_by;
}

/**
 * Display admin notice if requirements not met.
 */
function kashier_tutor_admin_notice() {
	if ( ! class_exists( 'TUTOR\Tutor' ) ) {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Kashier Payment Gateway for Tutor LMS', 'kashier-tutor-gateway' ); ?></strong>
				<?php esc_html_e( 'requires Tutor LMS to be installed and activated.', 'kashier-tutor-gateway' ); ?>
			</p>
		</div>
		<?php
		return;
	}

	$monetize_by = tutor_utils()->get_option( 'monetize_by' );
	if ( 'tutor' !== $monetize_by ) {
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Kashier Payment Gateway for Tutor LMS', 'kashier-tutor-gateway' ); ?></strong>
				<?php esc_html_e( 'requires Tutor LMS native eCommerce to be enabled. Please set "Monetize by" to "Native" in Tutor LMS settings.', 'kashier-tutor-gateway' ); ?>
			</p>
		</div>
		<?php
	}
}

/**
 * Initialize the plugin.
 */
function kashier_tutor_init() {
	// Load text domain.
	load_plugin_textdomain( 'kashier-tutor-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Check requirements.
	if ( ! kashier_tutor_check_requirements() ) {
		add_action( 'admin_notices', 'kashier_tutor_admin_notice' );
		return;
	}

	// Include required files.
	require_once KASHIER_TUTOR_PATH . 'includes/class-kashier-tutor-helper.php';
	require_once KASHIER_TUTOR_PATH . 'includes/class-kashier-tutor-payment-gateway.php';
	require_once KASHIER_TUTOR_PATH . 'includes/class-kashier-tutor-gateway.php';
	require_once KASHIER_TUTOR_PATH . 'includes/class-kashier-tutor-webhook.php';

	// Initialize gateway.
	Kashier_Tutor_Gateway::get_instance();
	Kashier_Tutor_Webhook::get_instance();
}
add_action( 'plugins_loaded', 'kashier_tutor_init', 20 );

/**
 * Activation hook.
 */
function kashier_tutor_activate() {
	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'kashier_tutor_activate' );

/**
 * Deactivation hook.
 */
function kashier_tutor_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'kashier_tutor_deactivate' );

