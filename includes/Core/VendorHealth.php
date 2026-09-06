<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the store-wide scan results already sitting in wp_wcsd_issues into
 * a PER-VENDOR health score, for Dokan multivendor marketplaces.
 *
 * Deliberately reuses the existing scan data rather than re-scanning per
 * vendor — a vendor's score is just "their slice" of the same issues table,
 * grouped by which vendor owns each product. In WooCommerce/Dokan, product
 * ownership is post_author, so no Dokan-specific product query is needed;
 * only the vendor LIST (who counts as a seller) is Dokan-specific.
 */
class VendorHealth {

	private $issue_manager;

	public function __construct( IssueManager $issue_manager ) {
		$this->issue_manager = $issue_manager;
	}

	/**
	 * Whether Dokan is active. Every public method below is meaningless
	 * without it, so callers should check this first.
	 */
	public static function is_dokan_active() {
		return function_exists( 'dokan' ) || class_exists( 'WeDevs_Dokan' );
	}

	/**
	 * Every Dokan seller's user ID. Falls back to querying the 'seller'
	 * role directly if dokan()'s vendor helper isn't available for some
	 * reason, so this keeps working across Dokan versions.
	 *
	 * @return int[]
	 */
	public function get_vendor_ids() {
		if ( function_exists( 'dokan_get_seller_ids' ) ) {
			return array_map( 'intval', dokan_get_seller_ids() );
		}

		$users = get_users( array(
			'role'   => 'seller',
			'fields' => 'ID',
		) );

		return array_map( 'intval', $users );
	}

	/**
	 * Product IDs (published) owned by one vendor.
	 *
	 * @return int[]
	 */
	public function get_vendor_product_ids( $vendor_id ) {
		$ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'author'         => (int) $vendor_id,
			'fields'         => 'ids',
			'posts_per_page' => -1,
		) );

		return array_map( 'intval', $ids );
	}

	/**
	 * One vendor's health score, computed from their slice of the existing
	 * open issues — not a fresh scan.
	 *
	 * @return array{
	 *   vendor_id:int, score:int, product_count:int,
	 *   critical:int, warning:int, suggestion:int
	 * }
	 */
	public function get_vendor_score( $vendor_id ) {
		$product_ids = $this->get_vendor_product_ids( $vendor_id );

		if ( empty( $product_ids ) ) {
			return array(
				'vendor_id'     => (int) $vendor_id,
				'score'         => 100,
				'product_count' => 0,
				'critical'      => 0,
				'warning'       => 0,
				'suggestion'    => 0,
			);
		}

		$issues = $this->issue_manager->get_open_issues_for_objects( $product_ids );

		$counts = array( 'critical' => 0, 'warning' => 0, 'suggestion' => 0 );
		foreach ( $issues as $issue ) {
			if ( isset( $counts[ $issue->severity ] ) ) {
				$counts[ $issue->severity ]++;
			}
		}

		$weighted = ScoreFormula::weighted_sum( wp_list_pluck( $issues, 'severity' ) );
		$score    = ScoreFormula::score( $weighted, count( $product_ids ) );

		return array(
			'vendor_id'     => (int) $vendor_id,
			'score'         => $score,
			'product_count' => count( $product_ids ),
			'critical'      => $counts['critical'],
			'warning'       => $counts['warning'],
			'suggestion'    => $counts['suggestion'],
		);
	}

	/**
	 * Everything the Dokan dashboard tab needs in one call: overall score,
	 * a per-scanner category breakdown (same shape as the store-wide
	 * Dashboard categories, but scoped to this vendor's products), and the
	 * vendor's own open issues list.
	 */
	public function get_vendor_report( $vendor_id ) {
		$product_ids = $this->get_vendor_product_ids( $vendor_id );
		$overall     = $this->get_vendor_score( $vendor_id );

		if ( empty( $product_ids ) ) {
			return array(
				'overall'    => $overall,
				'categories' => array(),
				'issues'     => array(),
			);
		}

		$details = $this->issue_manager->get_open_issues_details_for_objects( $product_ids );

		$by_scanner = array();
		foreach ( $details as $row ) {
			$by_scanner[ $row->scanner ][] = $row->severity;
		}

		$categories = array();
		foreach ( $by_scanner as $scanner_id => $severities ) {
			$weighted                  = ScoreFormula::weighted_sum( $severities );
			$categories[ $scanner_id ] = array(
				'label' => 'seo' === $scanner_id ? __( 'SEO', 'woocommerce-store-doctor' ) : ucwords( str_replace( '_', ' ', $scanner_id ) ),
				'score' => ScoreFormula::score( $weighted, count( $product_ids ) ),
			);
		}

		return array(
			'overall'    => $overall,
			'categories' => $categories,
			'issues'     => $details,
		);
	}

	/**
	 * Every vendor's score, sorted worst-first so the admin leaderboard
	 * (and a vendor's own dashboard ranking, if ever needed) surfaces the
	 * stores that most need attention.
	 *
	 * @return array<int, array> Same shape as get_vendor_score(), plus 'name'.
	 */
	public function get_all_vendor_scores() {
		$rows = array();

		foreach ( $this->get_vendor_ids() as $vendor_id ) {
			$row         = $this->get_vendor_score( $vendor_id );
			$user        = get_userdata( $vendor_id );
			$row['name'] = $user ? $user->display_name : sprintf( __( 'Vendor #%d', 'woocommerce-store-doctor' ), $vendor_id );
			$rows[]      = $row;
		}

		usort( $rows, function ( $a, $b ) {
			return $a['score'] <=> $b['score'];
		} );

		return $rows;
	}
}
