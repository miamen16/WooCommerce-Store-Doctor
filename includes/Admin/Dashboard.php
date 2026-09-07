<?php

namespace WCSD\Admin;

use WCSD\Core\IssueManager;
use WCSD\Core\ScoreManager;
use WCSD\Core\FixManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "Store Doctor" admin menu and renders the dashboard screen.
 */
class Dashboard {

	private $issue_manager;
	private $score_manager;
	private $fix_manager;

	public function __construct( IssueManager $issue_manager, ScoreManager $score_manager, FixManager $fix_manager ) {
		$this->issue_manager = $issue_manager;
		$this->score_manager = $score_manager;
		$this->fix_manager   = $fix_manager;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Store Doctor', 'store-doctor-for-woocommerce' ),
			__( 'Store Doctor', 'store-doctor-for-woocommerce' ),
			'manage_woocommerce',
			'wcsd-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-heart',
			56
		);

		add_submenu_page(
			'wcsd-dashboard',
			__( 'Dashboard', 'store-doctor-for-woocommerce' ),
			__( 'Dashboard', 'store-doctor-for-woocommerce' ),
			'manage_woocommerce',
			'wcsd-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'wcsd-dashboard',
			__( 'Issues', 'store-doctor-for-woocommerce' ),
			__( 'Issues', 'store-doctor-for-woocommerce' ),
			'manage_woocommerce',
			'wcsd-issues',
			array( $this, 'render_issues' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'wcsd-' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wcsd-admin', WCSD_URL . 'assets/css/admin.css', array(), WCSD_VERSION );
		wp_enqueue_script( 'wcsd-admin', WCSD_URL . 'assets/js/admin.js', array( 'jquery' ), WCSD_VERSION, true );

		$chart_data = array();
		if ( false !== strpos( $hook, 'wcsd-dashboard' ) ) {
			foreach ( $this->score_manager->get_history( 30 ) as $row ) {
				$chart_data[] = array(
					'label' => date_i18n( 'M j', strtotime( $row->scanned_at ) ),
					'score' => (int) $row->overall_score,
				);
			}
		}

		wp_localize_script( 'wcsd-admin', 'WCSD', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wcsd_admin' ),
			'history' => $chart_data,
			'i18n'    => array(
				'scanning'      => __( 'Scanning your store…', 'store-doctor-for-woocommerce' ),
				'error'         => __( 'Something went wrong. Please try again.', 'store-doctor-for-woocommerce' ),
				'loadingPreview' => __( 'Loading preview…', 'store-doctor-for-woocommerce' ),
				'applying'      => __( 'Applying changes…', 'store-doctor-for-woocommerce' ),
				'reverting'     => __( 'Reverting…', 'store-doctor-for-woocommerce' ),
				'confirmApply'  => __( 'Apply these changes? A backup is kept so you can revert.', 'store-doctor-for-woocommerce' ),
				'confirmRevert' => __( 'Revert this batch of changes?', 'store-doctor-for-woocommerce' ),
				'noAutoFix'     => __( 'None of these items could be fixed automatically — they need manual review.', 'store-doctor-for-woocommerce' ),
			),
		) );
	}

	public function render_dashboard() {
		$latest         = $this->score_manager->get_latest();
		$history        = $this->score_manager->get_history( 30 );
		$category_scores = $latest ? json_decode( $latest->category_scores, true ) : array();
		$last_scan      = get_option( 'wcsd_last_scan' );

		include WCSD_PATH . 'templates/dashboard.php';
	}

	public function render_issues() {
		$grouped = $this->issue_manager->get_grouped_summary();
		$batches = $this->fix_manager->get_recent_batches();

		include WCSD_PATH . 'templates/issues.php';
	}
}
