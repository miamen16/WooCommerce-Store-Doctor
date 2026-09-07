<?php

namespace WCSD\Dokan;

use WCSD\Core\VendorHealth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a "Store Health" tab to a vendor's own Dokan dashboard
 * (yourshop.com/dashboard/store-health), read-only — showing that
 * vendor's own score, category breakdown, and open issues, computed from
 * VendorHealth (their slice of the store-wide scan). Auto-fixing stays an
 * admin-only capability; vendors get visibility, not write access.
 *
 * Only instantiated when VendorHealth::is_dokan_active() (see Plugin::init()).
 * Uses Dokan's documented third-party dashboard tab extension pattern:
 * dokan_query_var_filter + dokan_get_dashboard_nav + dokan_load_custom_template.
 * NOTE: written against Dokan's public hook API but not exercised against a
 * live Dokan install in this environment — worth a smoke test on a real
 * Dokan site before shipping.
 */
class VendorDashboardTab {

	const ENDPOINT = 'store-health';

	private $vendor_health;

	public function __construct( VendorHealth $vendor_health ) {
		$this->vendor_health = $vendor_health;

		add_filter( 'dokan_query_var_filter', array( $this, 'register_query_var' ) );
		add_filter( 'dokan_get_dashboard_nav', array( $this, 'register_nav_item' ) );
		add_filter( 'dokan_load_custom_template', array( $this, 'maybe_load_template' ) );
	}

	public function register_query_var( $query_vars ) {
		$query_vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $query_vars;
	}

	public function register_nav_item( $urls ) {
		$urls[ self::ENDPOINT ] = array(
			'title' => __( 'Store Health', 'store-doctor-for-woocommerce' ),
			'icon'  => '<i class="fas fa-heartbeat"></i>',
			'url'   => function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url( self::ENDPOINT ) : '',
			'pos'   => 51,
		);

		return $urls;
	}

	public function maybe_load_template( $template ) {
		global $wp;

		if ( isset( $wp->query_vars[ self::ENDPOINT ] ) ) {
			return WCSD_PATH . 'templates/dokan-vendor-health.php';
		}

		return $template;
	}
}
