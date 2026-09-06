<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Computes the overall Store Health score and records history snapshots.
 */
class ScoreManager {

	/**
	 * Simple average of category scores for now.
	 * Later this can become weighted (e.g. Orders/Inventory weighted higher
	 * than Suggestions-only categories) without changing the calling code.
	 *
	 * @param array $category_scores [ 'product' => ['label'=>..,'score'=>..], ... ]
	 */
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

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'overall_score'     => (int) $overall_score,
				'category_scores'   => wp_json_encode( $category_scores ),
				'issues_critical'   => (int) $counts['critical'],
				'issues_warning'    => (int) $counts['warning'],
				'issues_suggestion' => (int) $counts['suggestion'],
				'scanned_at'        => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get the most recent N history rows, oldest first, for the trend graph.
	 */
	public function get_history( $limit = 30 ) {
		global $wpdb;
		$table = Database::history_table();

		// $table is generated internally by Database::history_table() and is
		// never populated from request data. It is a trusted SQL identifier.
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
				"SELECT * FROM (
					SELECT * FROM {$table} ORDER BY scanned_at DESC LIMIT %d
				 ) t ORDER BY scanned_at ASC",
				(int) $limit
			)
		);

		return $rows;
	}

	public function get_latest() {
		global $wpdb;
		$table = Database::history_table();

		// Trusted table identifier from Database::history_table(); no request data is interpolated.
		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			"SELECT * FROM {$table} ORDER BY scanned_at DESC LIMIT 1"
		);
	}
}
