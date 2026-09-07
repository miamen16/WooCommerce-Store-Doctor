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

	// Trusted SQL table identifiers are generated internally by Database; dynamic values use prepare().
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

	public function replace_for_scanner( $scanner_id, array $issues ) {
		global $wpdb;
		$table = Database::issues_table();
		$now   = current_time( 'mysql' );
		$wpdb->delete( $table, array( 'scanner' => $scanner_id ) );
		foreach ( $issues as $issue ) {
			$wpdb->insert( $table, array(
				'scanner' => $scanner_id,
				'type' => $issue['type'],
				'severity' => $issue['severity'],
				'object_type' => $issue['object_type'],
				'object_id' => $issue['object_id'],
				'message' => $issue['message'],
				'fixable' => ! empty( $issue['fixable'] ) ? 1 : 0,
				'fixer' => $issue['fixer'],
				'status' => 'open',
				'meta' => ! empty( $issue['meta'] ) ? wp_json_encode( $issue['meta'] ) : null,
				'created_at' => $now,
				'updated_at' => $now,
			) );
		}
	}

	public function get_issues( $args = array() ) {
		global $wpdb;
		$table = Database::issues_table();
		$defaults = array(
			'severity' => null,
			'scanner' => null,
			'status' => 'open',
			'orderby' => 'severity',
			'limit' => 200,
		);
		$args = wp_parse_args( $args, $defaults );
		$where = array( '1=1' );
		$params = array();
		if ( $args['status'] ) {
			$where[] = 'status = %s';
			$params[] = $args['status'];
		}
		if ( $args['severity'] ) {
			$where[] = 'severity = %s';
			$params[] = $args['severity'];
		}
		if ( $args['scanner'] ) {
			$where[] = 'scanner = %s';
			$params[] = $args['scanner'];
		}
		$sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where )
			. ' ORDER BY FIELD(severity, \'critical\',\'warning\',\'suggestion\'), id DESC'
			. ' LIMIT %d';
		$params[] = (int) $args['limit'];
		return $wpdb->get_results(
			$wpdb->prepare( $sql, $params )
		);
	}

	public function get_grouped_summary() {
		global $wpdb;
		$table = Database::issues_table();
		$sql = 'SELECT type, severity, scanner, COUNT(*) as total, MAX(fixable) as fixable
			 FROM ' . $table . '
			 WHERE status = \'open\'
			 GROUP BY type, severity, scanner
			 ORDER BY FIELD(severity, \'critical\',\'warning\',\'suggestion\'), total DESC';
		return $wpdb->get_results( $sql );
	}

	public function get_issues_by_type( $type, $status = 'open' ) {
		global $wpdb;
		$table = Database::issues_table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, object_type, object_id, fixer FROM {$table} WHERE type = %s AND status = %s",
				$type,
				$status
			)
		);
	}

	public function get_object_ids_by_type( $type, $status = 'open' ) {
		global $wpdb;
		$table = Database::issues_table();
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT object_id FROM {$table} WHERE type = %s AND status = %s AND object_type = 'product'",
				$type,
				$status
			)
		);
		return array_map( 'intval', $ids );
	}

	public function get_object_ids_by_scanner( $scanner, $status = 'open' ) {
		global $wpdb;
		$table = Database::issues_table();
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT object_id FROM {$table} WHERE scanner = %s AND status = %s AND object_type = 'product'",
				$scanner,
				$status
			)
		);
		return array_map( 'intval', $ids );
	}

	public function get_open_issues_for_objects( array $object_ids ) {
		global $wpdb;
		$table = Database::issues_table();
		if ( empty( $object_ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $object_ids ), '%d' ) );
		$sql = 'SELECT severity FROM ' . $table . ' WHERE status = \'open\' AND object_type = \'product\' AND object_id IN (' . $placeholders . ')';
		return $wpdb->get_results( $wpdb->prepare( $sql, $object_ids ) );
	}

	public function get_open_issues_details_for_objects( array $object_ids, $limit = 200 ) {
		global $wpdb;
		$table = Database::issues_table();
		if ( empty( $object_ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $object_ids ), '%d' ) );
		$params = $object_ids;
		$params[] = (int) $limit;
		$sql = 'SELECT scanner, type, severity, message, object_id FROM ' . $table . '
			 WHERE status = \'open\' AND object_type = \'product\' AND object_id IN (' . $placeholders . ')
			 ORDER BY FIELD(severity, \'critical\',\'warning\',\'suggestion\')
			 LIMIT %d';
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public function mark_resolved( $issue_id ) {
		global $wpdb;
		$table = Database::issues_table();
		return $wpdb->update( $table, array(
			'status' => 'resolved',
			'updated_at' => current_time( 'mysql' ),
		), array( 'id' => (int) $issue_id ) );
	}

	// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
}
