<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Computes the overall Store Health score and records history snapshots.
 */
class ScoreManager {

	// Trusted SQL table identifiers are generated internally by Database; dynamic values use prepare().
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching

	public function calculate_overall( array $category_scores ) {
		if ( empty( $category_scores ) ) {
			return 0;
		}
		$total = 0;
		foreach ( $category_scores as $cat ) {
			$total += $cat['score'];
		}
		return (int) round( $total / count( $category_scores ) );
	}

	public function record_history( $overall_score, array $category_scores, array $counts ) {
		global $wpdb;
		$table = Database::history_table();
		$wpdb->insert( $table, array(
			'overall_score' => (int) $overall_score,
			'category_scores' => wp_json_encode( $category_scores ),
			'issues_critical' => (int) $counts['critical'],
			'issues_warning' => (int) $counts['warning'],
			'issues_suggestion' => (int) $counts['suggestion'],
			'scanned_at' => current_time( 'mysql' ),
		) );
	}

	public function get_history( $limit = 30 ) {
		global $wpdb;
		$table = Database::history_table();
		$sql = 'SELECT * FROM (\n\t\t\t\tSELECT * FROM ' . $table . ' ORDER BY scanned_at DESC LIMIT %d\n\t\t\t ) t ORDER BY scanned_at ASC';
		return $wpdb->get_results( $wpdb->prepare( $sql, (int) $limit ) );
	}

	public function get_latest() {
		global $wpdb;
		$table = Database::history_table();
		$sql = 'SELECT * FROM ' . $table . ' ORDER BY scanned_at DESC LIMIT 1';
		return $wpdb->get_row( $sql );
	}

	// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
}
