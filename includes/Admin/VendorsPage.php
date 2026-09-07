<?php

namespace WCSD\Admin;

use WCSD\Core\VendorHealth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-side "Vendors" leaderboard under the Store Doctor menu — only
 * registered when Dokan is active (see Plugin::init()). Shows every
 * vendor's health score, worst-first, computed from the existing scan
 * data (see VendorHealth) rather than a separate per-vendor scan.
 */
class VendorsPage {

	private $vendor_health;

	public function __construct( VendorHealth $vendor_health ) {
		$this->vendor_health = $vendor_health;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	public function register_menu() {
		add_submenu_page(
			'wcsd-dashboard',
			__( 'Vendors', 'store-doctor-for-woocommerce' ),
			__( 'Vendors', 'store-doctor-for-woocommerce' ),
			'manage_woocommerce',
			'wcsd-vendors',
			array( $this, 'render' )
		);
	}

	public function render() {
		$vendors = $this->vendor_health->get_all_vendor_scores();

		include WCSD_PATH . 'templates/vendors.php';
	}
}
