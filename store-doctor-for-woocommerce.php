<?php
/**
 * Plugin Name: Store Doctor for WooCommerce
 * Plugin URI:  https://github.com/miamen16/store-doctor-for-woocommerce
 * Description: Diagnose your WooCommerce store. Fix what hurts. Grow what works.
 * Version:     1.0.0
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

define( 'WCSD_VERSION', '1.0.0' );
define( 'WCSD_FILE', __FILE__ );
define( 'WCSD_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCSD_URL', plugin_dir_url( __FILE__ ) );
define( 'WCSD_DB_VERSION', '1.1.0' );

/**
 * Simple PSR-4-ish autoloader for the WCSD\ namespace.
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
	esc_html_e( 'Store Doctor for WooCommerce requires WooCommerce to be installed and active.', 'store-doctor-for-woocommerce' );
	echo '</p></div>';
}

/**
 * Add a native WordPress "View details" link to the plugin row.
 */
function wcsd_plugin_action_links( $actions, $plugin_file ) {
	if ( plugin_basename( WCSD_FILE ) !== $plugin_file ) {
		return $actions;
	}

	$details_link = '<a href="#wcsd-plugin-details-modal" class="wcsd-view-details" aria-label="' . esc_attr__( 'View Store Doctor for WooCommerce details', 'store-doctor-for-woocommerce' ) . '">' . esc_html__( 'View details', 'store-doctor-for-woocommerce' ) . '</a>';
	$actions[]    = $details_link;

	return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wcsd_plugin_action_links' );

/**
 * Load the plugin details UI only on the Plugins screen.
 */
function wcsd_plugin_details_assets( $hook ) {
	if ( 'plugins.php' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'wcsd-plugin-details', WCSD_URL . 'assets/css/plugin-details.css', array(), WCSD_VERSION );
	wp_enqueue_script( 'wcsd-plugin-details', WCSD_URL . 'assets/js/plugin-details.js', array(), WCSD_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'wcsd_plugin_details_assets' );

/**
 * Render the plugin details modal on the Plugins screen.
 */
function wcsd_plugin_details_modal() {
	$description = __( 'Store Doctor for WooCommerce scans your store and turns problems into actionable recommendations. It checks product completeness, images, inventory, pricing, basic SEO, store health scoring, issue management and safe auto-fixes.', 'store-doctor-for-woocommerce' );
	?>
	<div id="wcsd-plugin-details-modal" class="wcsd-plugin-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="wcsd-plugin-details-title">
		<div class="wcsd-plugin-modal__dialog">
			<div class="wcsd-plugin-modal__header">
				<h2 id="wcsd-plugin-details-title" class="wcsd-plugin-modal__title"><?php esc_html_e( 'Store Doctor for WooCommerce', 'store-doctor-for-woocommerce' ); ?></h2>
				<button type="button" class="wcsd-plugin-modal__close" aria-label="<?php esc_attr_e( 'Close', 'store-doctor-for-woocommerce' ); ?>">&times;</button>
			</div>
			<div class="wcsd-plugin-modal__body">
				<dl class="wcsd-plugin-modal__meta">
					<div><dt><?php esc_html_e( 'Version', 'store-doctor-for-woocommerce' ); ?></dt><dd><?php echo esc_html( WCSD_VERSION ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Requires PHP', 'store-doctor-for-woocommerce' ); ?></dt><dd>7.4+</dd></div>
					<div><dt><?php esc_html_e( 'Requires', 'store-doctor-for-woocommerce' ); ?></dt><dd>WooCommerce</dd></div>
					<div><dt><?php esc_html_e( 'License', 'store-doctor-for-woocommerce' ); ?></dt><dd>GPLv2 or later</dd></div>
				</dl>

				<div class="wcsd-plugin-modal__tabs" role="tablist">
					<button type="button" class="wcsd-plugin-modal__tab is-active" data-target="#wcsd-details-description" role="tab" aria-selected="true"><?php esc_html_e( 'Description', 'store-doctor-for-woocommerce' ); ?></button>
					<button type="button" class="wcsd-plugin-modal__tab" data-target="#wcsd-details-installation" role="tab" aria-selected="false"><?php esc_html_e( 'Installation', 'store-doctor-for-woocommerce' ); ?></button>
					<button type="button" class="wcsd-plugin-modal__tab" data-target="#wcsd-details-faq" role="tab" aria-selected="false"><?php esc_html_e( 'FAQ', 'store-doctor-for-woocommerce' ); ?></button>
					<button type="button" class="wcsd-plugin-modal__tab" data-target="#wcsd-details-changelog" role="tab" aria-selected="false"><?php esc_html_e( 'Changelog', 'store-doctor-for-woocommerce' ); ?></button>
					<button type="button" class="wcsd-plugin-modal__tab" data-target="#wcsd-details-screenshots" role="tab" aria-selected="false"><?php esc_html_e( 'Screenshots', 'store-doctor-for-woocommerce' ); ?></button>
				</div>

				<section id="wcsd-details-description" class="wcsd-plugin-modal__panel is-active" role="tabpanel">
					<p><?php echo esc_html( $description ); ?></p>
					<h3><?php esc_html_e( 'What it checks', 'store-doctor-for-woocommerce' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Product titles, descriptions, SKUs, categories and cross-sells.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Featured and gallery images.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Inventory and variation-level stock issues.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Pricing and variation-level price issues.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Basic SEO and image alt text.', 'store-doctor-for-woocommerce' ); ?></li>
					</ul>
				</section>

				<section id="wcsd-details-installation" class="wcsd-plugin-modal__panel" role="tabpanel">
					<h3><?php esc_html_e( 'Installation', 'store-doctor-for-woocommerce' ); ?></h3>
					<ol>
						<li><?php esc_html_e( 'Install and activate WooCommerce.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Upload the Store Doctor for WooCommerce ZIP from Plugins → Add New → Upload Plugin.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Activate Store Doctor for WooCommerce.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Open Store Doctor from the WordPress admin menu and run the first scan.', 'store-doctor-for-woocommerce' ); ?></li>
					</ol>
				</section>

				<section id="wcsd-details-faq" class="wcsd-plugin-modal__panel" role="tabpanel">
					<div class="wcsd-plugin-modal__faq-item"><h4><?php esc_html_e( 'Does it change my products automatically?', 'store-doctor-for-woocommerce' ); ?></h4><p><?php esc_html_e( 'Only supported issues can be auto-fixed. The plugin shows a preview first and keeps backups for supported fixes.', 'store-doctor-for-woocommerce' ); ?></p></div>
					<div class="wcsd-plugin-modal__faq-item"><h4><?php esc_html_e( 'Can I revert an automatic fix?', 'store-doctor-for-woocommerce' ); ?></h4><p><?php esc_html_e( 'Yes. Supported fixes are stored in batches and can be reverted from the Recent Fixes section.', 'store-doctor-for-woocommerce' ); ?></p></div>
					<div class="wcsd-plugin-modal__faq-item"><h4><?php esc_html_e( 'Does it support variable products?', 'store-doctor-for-woocommerce' ); ?></h4><p><?php esc_html_e( 'Yes. Inventory and pricing checks inspect individual variations.', 'store-doctor-for-woocommerce' ); ?></p></div>
				</section>

				<section id="wcsd-details-changelog" class="wcsd-plugin-modal__panel" role="tabpanel">
					<h3>1.0.0</h3>
					<ul class="wcsd-plugin-modal__changelog">
						<li><?php esc_html_e( 'Initial stable release.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Product, image, inventory, pricing and basic SEO scanners.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Health scoring, issue center, history and safe auto-fix engine.', 'store-doctor-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Dokan vendor health integration.', 'store-doctor-for-woocommerce' ); ?></li>
					</ul>
				</section>

				<section id="wcsd-details-screenshots" class="wcsd-plugin-modal__panel" role="tabpanel">
					<h3><?php esc_html_e( 'Dashboard', 'store-doctor-for-woocommerce' ); ?></h3>
					<img class="wcsd-plugin-modal__screenshot" src="<?php echo esc_url( WCSD_URL . 'assets/images/plugin-screenshot-1.svg' ); ?>" alt="<?php esc_attr_e( 'Store Doctor dashboard screenshot', 'store-doctor-for-woocommerce' ); ?>" />
				</section>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'admin_footer-plugins.php', 'wcsd_plugin_details_modal' );

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
