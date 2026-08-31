<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central singleton that wires the plugin together.
 */
class Plugin {

	private static $instance = null;

	public $scanner;
	public $issue_manager;
	public $score_manager;
	public $scheduler;
	public $fix_manager;
	public $vendor_health;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init() {
		Database::maybe_upgrade();

		$this->issue_manager = new IssueManager();
		$this->score_manager = new ScoreManager();
		$this->scanner        = new Scanner( $this->issue_manager, $this->score_manager );
		$this->scheduler      = new Scheduler( $this->scanner );
		$this->fix_manager    = new FixManager( $this->issue_manager );
		$this->vendor_health  = new VendorHealth( $this->issue_manager );

		$this->register_scanners();
		$this->register_fixers();

		$this->scheduler->register_hooks();

		if ( is_admin() ) {
			new \WCSD\Admin\Dashboard( $this->issue_manager, $this->score_manager, $this->fix_manager );
			new \WCSD\Admin\ProductFilter( $this->issue_manager );

			if ( VendorHealth::is_dokan_active() ) {
				new \WCSD\Admin\VendorsPage( $this->vendor_health );
			}
		}

		if ( VendorHealth::is_dokan_active() ) {
			new \WCSD\Dokan\VendorDashboardTab( $this->vendor_health );
		}

		$this->register_ajax_hooks();
	}

	/**
	 * Register the built-in scanners. Third-party code can hook 'wcsd_register_scanners'
	 * to add their own (e.g. a future Dokan vendor-health scanner).
	 */
	private function register_scanners() {
		$this->scanner->add( new \WCSD\Scanners\ProductScanner() );
		$this->scanner->add( new \WCSD\Scanners\ImageScanner() );
		$this->scanner->add( new \WCSD\Scanners\InventoryScanner() );
		$this->scanner->add( new \WCSD\Scanners\PricingScanner() );
		$this->scanner->add( new \WCSD\Scanners\SEOScanner() );

		do_action( 'wcsd_register_scanners', $this->scanner );
	}

	/**
	 * Register the built-in fixers. Third-party code can hook 'wcsd_register_fixers'
	 * to add their own, matching the 'fixer' id it attaches to issues.
	 */
	private function register_fixers() {
		$this->fix_manager->add( new \WCSD\Fixers\CategoryFixer() );
		$this->fix_manager->add( new \WCSD\Fixers\FeaturedImageFixer() );
		$this->fix_manager->add( new \WCSD\Fixers\ImageAltTextFixer() );

		do_action( 'wcsd_register_fixers', $this->fix_manager );
	}

	private function register_ajax_hooks() {
		add_action( 'wp_ajax_wcsd_run_scan', array( $this, 'ajax_run_scan' ) );
		add_action( 'wp_ajax_wcsd_fix_preview', array( $this, 'ajax_fix_preview' ) );
		add_action( 'wp_ajax_wcsd_fix_apply', array( $this, 'ajax_fix_apply' ) );
		add_action( 'wp_ajax_wcsd_fix_revert', array( $this, 'ajax_fix_revert' ) );
	}

	private function guard() {
		check_ajax_referer( 'wcsd_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wc-store-doctor' ) ), 403 );
		}
	}

	/**
	 * AJAX handler for the "Scan Store" button in the dashboard.
	 */
	public function ajax_run_scan() {
		$this->guard();

		$result = $this->scanner->run_full_scan();

		wp_send_json_success( $result );
	}

	/**
	 * Step 1 of the fix flow: build a preview of what would change.
	 * Nothing is written to the store yet.
	 */
	public function ajax_fix_preview() {
		$this->guard();

		$issue_type = isset( $_POST['issue_type'] ) ? sanitize_key( wp_unslash( $_POST['issue_type'] ) ) : '';
		if ( ! $issue_type ) {
			wp_send_json_error( array( 'message' => __( 'Missing issue type.', 'wc-store-doctor' ) ), 400 );
		}

		$result = $this->fix_manager->preview_for_type( $issue_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Step 2 of the fix flow: apply after the user confirmed the preview.
	 * Every change is backed up so it can be reverted.
	 */
	public function ajax_fix_apply() {
		$this->guard();

		$issue_type = isset( $_POST['issue_type'] ) ? sanitize_key( wp_unslash( $_POST['issue_type'] ) ) : '';
		if ( ! $issue_type ) {
			wp_send_json_error( array( 'message' => __( 'Missing issue type.', 'wc-store-doctor' ) ), 400 );
		}

		$result = $this->fix_manager->apply_for_type( $issue_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Revert a previously applied fix batch.
	 */
	public function ajax_fix_revert() {
		$this->guard();

		$batch_id = isset( $_POST['batch_id'] ) ? sanitize_text_field( wp_unslash( $_POST['batch_id'] ) ) : '';
		if ( ! $batch_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing batch id.', 'wc-store-doctor' ) ), 400 );
		}

		$result = $this->fix_manager->revert_batch( $batch_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( $result );
	}
}
