<?php
/**
 * Plugin Name: Store Doctor for WooCommerce
 * Plugin URI:  https://github.com/miamen16/WooCommerce-Store-Doctor
 * Description: Diagnose your WooCommerce store. Fix what hurts. Grow what works.
 * Version:     1.0.0-alpha
 * Author:      Mohamed
 * Text Domain: store-doctor-for-woocommerce
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'WCSD_VERSION', '1.0.0-alpha' );
define( 'WCSD_FILE', __FILE__ );
define( 'WCSD_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCSD_URL', plugin_dir_url( __FILE__ ) );
define( 'WCSD_DB_VERSION', '1.1.0' );

/**
 * Simple PSR-4-ish autoloader for the WCSD\ namespace.
 * WCSD\Core\Plugin        -> includes/Core/Plugin.php
 * WCSD\Scanners\ProductScanner -> includes/Scanners/ProductScanner.php
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'WCSD\\';

	if ( strpos( $class, $prefix ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix ) );
	$relative_path  = str_replace( '\\', '/', $relative_class ) . '.php';
	$file           = WCSD_PATH . 'includes/' . $relative_path;

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Bail early with an admin notice if WooCommerce is not active.
 */
function wcsd_woocommerce_missing_notice() {
	echo '<div class="notice notice-error"><p>';
	esc_html_e( 'WooCommerce Store Doctor requires WooCommerce to be installed and active.', 'store-doctor-for-woocommerce' );
	echo '</p></div>';
}

/**
 * Boot the plugin once all plugins are loaded, so we know WooCommerce is available.
 */
function wcsd_boot() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wcsd_woocommerce_missing_notice' );
		return;
	}

	\WCSD\Core\Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'wcsd_boot' );

/**
 * Activation: create DB tables + schedule the default scan.
 */
function wcsd_activate() {
	require_once WCSD_PATH . 'includes/Core/Database.php';
	\WCSD\Core\Database::install();

	if ( ! wp_next_scheduled( 'wcsd_scheduled_scan' ) ) {
		wp_schedule_event( time(), 'daily', 'wcsd_scheduled_scan' );
	}
}
register_activation_hook( __FILE__, 'wcsd_activate' );

/**
 * Deactivation: clear the cron, keep the data (uninstall.php handles full cleanup).
 */
function wcsd_deactivate() {
	wp_clear_scheduled_hook( 'wcsd_scheduled_scan' );
}
register_deactivation_hook( __FILE__, 'wcsd_deactivate' );
