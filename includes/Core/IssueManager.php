<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persists issues to the wp_wcsd_issues table.
 *
 * Strategy: each scan for a given scanner REPLACES that scanner's rows.
 * This keeps the table as a "current snapshot" rather than an ever-growing
 * log — history/trends are tracked separately in wp_wcsd_health_history.
 */
class IssueManager {

	/**
	 * Replace all stored issues for one scanner with a freshly scanned set.
	 *
	 * @param string $scanner_id
	 * @param array  $issues
	 */
	public function replace_for_scanner( $scanner_id, array $issues ) {
		global $wpdb;
		$table = Database::issues_table();
		$now   = current_time( 'mysql' );

		// Wipe this scanner's previous snapshot.
		$wpdb->delete( $table, array( 'scanner' => $scanner_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		foreach ( $issues as $issue ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				array(
					'scanner'     => $scanner_id,
					'type'        => $issue['type'],
					'severity'    => $issue['severity'],
					'object_type' => $issue['object_type'],
					'object_id'   => $issue['object_id'],
					'message'     => $issue['message'],
					'fixable'     => ! empty( $issue['fixable'] ) ? 1 : 0,
					'fixer'       => $issue['fixer'],
					'status'      => 'open',
					'meta'        => ! empty( $issue['meta'] ) ? wp_json_encode( $issue['meta'] ) : null,
					'created_at'  => $now,
					'updated_at'  => $now,
				)
			);
		}
	}

	/**
	 * Fetch open issues, optionally filtered by severity and/or scanner.
	 */
	public function get_issues( $args = array() ) {
		global $wpdb;
		$table = Database::issues_table();

		$defaults = array(
			'severity' => null,
			'scanner'  => null,
			'status'   => 'open',
			'orderby'  => 'severity',
			'limit'    => 200,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		if ( $args['severity'] ) {
			$where[]  = 'severity = %s';
			$params[] = $args['severity'];
		}
		if ( $args['scanner'] ) {
			$where[]  = 'scanner = %s';
			$params[] = $args['scanner'];
		}

		// $table is generated internally by Database::issues_table() and is
		// never populated from request data. It is a trusted SQL identifier.
		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			. ' ORDER BY FIELD(severity, \'critical\',\'warning\',\'suggestion\'), id DESC'
			. ' LIMIT %d';
		$params[] = (int) $args['limit'];

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders
	}

	/**
	 * Group counts by "type" for the Issue Center table
	 * (e.g. "Missing images: 127 products").
	 */
	public function get_grouped_summary() {
		global $wpdb;
		$table = Database::issues_table();

		// The table identifier is generated internally by Database and is not
		// user-controlled; prepare() cannot safely substitute SQL identifiers.
		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT type, severity, scanner, COUNT(*) as total, MAX(fixable) as fixable
			 FROM {$table}
			 WHERE status = 'open'
			 GROUP BY type, severity, scanner
			 ORDER BY FIELD(severity, 'critical','warning','suggestion'), total DESC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Open issues of one type, e.g. all 'missing_category' rows. Used by the
	 * Fix Engine to know which objects/issue-ids a fixer run should touch.
	 */
	public function get_issues_by_type( $type, $status = 'open' ) {
		global $wpdb;
		$table = Database::issues_table();

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT id, object_type, object_id, fixer FROM {$table} WHERE type = %s AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$type,
				$status
			)
		);
	}

	/**
	 * Distinct product IDs with an open issue of one exact type, e.g. every
	 * product behind the "zero_price" row in the Issue Center. Used to build
	 * the "click the count -> filtered product list" links.
	 */
	public function get_object_ids_by_type( $type, $status = 'open' ) {
		global $wpdb;
		$table = Database::issues_table();

		$ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT DISTINCT object_id FROM {$table} WHERE type = %s AND status = %s AND object_type = 'product'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$type,
				$status
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * Distinct product IDs with ANY open issue from one scanner, e.g. every
	 * product behind the "Products" category score on the Dashboard.
	 */
	public function get_object_ids_by_scanner( $scanner, $status = 'open' ) {
		global $wpdb;
		$table = Database::issues_table();

		$ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT DISTINCT object_id FROM {$table} WHERE scanner = %s AND status = %s AND object_type = 'product'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$scanner,
				$status
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * All open issues belonging to a given set of object IDs (e.g. one
	 * vendor's product IDs), regardless of type or scanner. Used by
	 * VendorHealth to compute a per-vendor score from the store-wide scan
	 * data without re-scanning.
	 *
	 * @param int[] $object_ids
	 */
	public function get_open_issues_for_objects( array $object_ids ) {
		global $wpdb;
		$table = Database::issues_table();

		if ( empty( $object_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $object_ids ), '%d' ) );

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT severity FROM {$table} WHERE status = 'open' AND object_type = 'product' AND object_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
				$object_ids
			)
		);
	}

	/**
	 * Full issue rows (scanner, type, severity, message, object_id) for a
	 * given set of object IDs — used to build a vendor's own issue list on
	 * their Dokan dashboard tab.
	 *
	 * @param int[] $object_ids
	 */
	public function get_open_issues_details_for_objects( array $object_ids, $limit = 200 ) {
		global $wpdb;
		$table = Database::issues_table();

		if ( empty( $object_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $object_ids ), '%d' ) );
		$params       = $object_ids;
		$params[]     = (int) $limit;

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT scanner, type, severity, message, object_id FROM {$table}
				 WHERE status = 'open' AND object_type = 'product' AND object_id IN ({$placeholders})
				 ORDER BY FIELD(severity, 'critical','warning','suggestion')
				 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
				$params
			)
		);
	}

	public function mark_resolved( $issue_id ) {
		global $wpdb;
		$table = Database::issues_table();
		return $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'status'     => 'resolved',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $issue_id )
		);
	}
}
