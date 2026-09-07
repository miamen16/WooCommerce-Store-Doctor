<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrates the Auto Fix Engine: Preview -> Confirm -> Apply -> (optional) Revert.
 *
 * Nothing in this class writes to a product directly — that's the fixer's job.
 * FixManager's job is: find which objects have the issue, ask the fixer to
 * preview/apply/revert, and keep the backup + issue-status bookkeeping straight.
 */
class FixManager {

	/** @var AbstractFixer[] */
	private $fixers = array();

	private $issue_manager;

	public function __construct( IssueManager $issue_manager ) {
		$this->issue_manager = $issue_manager;
	}

	public function add( AbstractFixer $fixer ) {
		$this->fixers[ $fixer->id() ] = $fixer;
	}

	public function get( $id ) {
		return isset( $this->fixers[ $id ] ) ? $this->fixers[ $id ] : null;
	}

	public function preview_for_type( $issue_type ) {
		$rows = $this->issue_manager->get_issues_by_type( $issue_type );
		if ( empty( $rows ) ) {
			return new \WP_Error( 'wcsd_no_issues', __( 'No open issues of this type were found.', 'woocommerce-store-doctor' ) );
		}
		$fixer_id = $rows[0]->fixer;
		$fixer = $this->get( $fixer_id );
		if ( ! $fixer ) {
			return new \WP_Error( 'wcsd_no_fixer', __( 'This issue type has no automated fix.', 'woocommerce-store-doctor' ) );
		}
		$object_ids = wp_list_pluck( $rows, 'object_id' );
		$items = $fixer->preview( $object_ids );
		return array(
			'fixer_id' => $fixer_id,
			'fixer_label' => $fixer->label(),
			'issue_type' => $issue_type,
			'items' => array_slice( $items, 0, 25 ),
			'total' => count( $items ),
		);
	}

	public function apply_for_type( $issue_type ) {
		$rows = $this->issue_manager->get_issues_by_type( $issue_type );
		if ( empty( $rows ) ) {
			return new \WP_Error( 'wcsd_no_issues', __( 'No open issues of this type were found.', 'woocommerce-store-doctor' ) );
		}
		$fixer_id = $rows[0]->fixer;
		$fixer = $this->get( $fixer_id );
		if ( ! $fixer ) {
			return new \WP_Error( 'wcsd_no_fixer', __( 'This issue type has no automated fix.', 'woocommerce-store-doctor' ) );
		}
		$issue_by_object = array();
		foreach ( $rows as $row ) {
			$issue_by_object[ (int) $row->object_id ] = (int) $row->id;
		}
		$object_ids = array_keys( $issue_by_object );
		$results = $fixer->apply( $object_ids );
		if ( empty( $results ) ) {
			return new \WP_Error( 'wcsd_nothing_fixed', __( 'None of these items could be fixed automatically.', 'woocommerce-store-doctor' ) );
		}
		$batch_id = 'wcsd_' . substr( wp_generate_password( 12, false, false ), 0, 12 ) . '_' . time();
		$resolved_ids = array();
		foreach ( $results as $result ) {
			$issue_id = isset( $issue_by_object[ $result['object_id'] ] ) ? $issue_by_object[ $result['object_id'] ] : null;
			$this->record_backup( $batch_id, $fixer_id, $issue_id, 'product', $result['object_id'], $result['old_value'], $result['new_value'], isset( $result['meta'] ) ? $result['meta'] : null );
			if ( $issue_id ) {
				$resolved_ids[] = $issue_id;
			}
		}
		foreach ( $resolved_ids as $issue_id ) {
			$this->issue_manager->mark_resolved( $issue_id );
		}
		return array(
			'batch_id' => $batch_id,
			'fixed' => count( $results ),
			'fixer_label' => $fixer->label(),
		);
	}

	public function revert_batch( $batch_id ) {
		global $wpdb;
		$table = Database::backups_table();
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare(
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
				"SELECT * FROM " . $table . " WHERE batch_id = %s AND reverted = 0",
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
				$batch_id
			)
		);
		if ( empty( $rows ) ) {
			return new \WP_Error( 'wcsd_nothing_to_revert', __( 'Nothing to revert for this batch.', 'woocommerce-store-doctor' ) );
		}
		$reverted = 0;
		foreach ( $rows as $row ) {
			$fixer = $this->get( $row->fixer );
			if ( ! $fixer ) {
				continue;
			}
			$row->meta = $row->meta ? json_decode( $row->meta, true ) : null;
			$fixer->revert_one( $row );
			$wpdb->update( $table, array( 'reverted' => 1 ), array( 'id' => $row->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$reverted++;
		}
		return array( 'reverted' => $reverted );
	}

	public function get_recent_batches( $limit = 10 ) {
		global $wpdb;
		$table = Database::backups_table();
		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare(
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
				"SELECT batch_id, fixer,
					COUNT(*) as total,
					SUM(reverted) as reverted_count,
					MIN(created_at) as created_at
				 FROM " . $table . "
				 GROUP BY batch_id, fixer
				 ORDER BY created_at DESC
				 LIMIT %d",
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
				(int) $limit
			)
		);
	}

	private function record_backup( $batch_id, $fixer_id, $issue_id, $object_type, $object_id, $old_value, $new_value, $meta ) {
		global $wpdb;
		$table = Database::backups_table();
		$wpdb->insert( $table, array(
			'batch_id' => $batch_id,
			'fixer' => $fixer_id,
			'issue_id' => $issue_id,
			'object_type' => $object_type,
			'object_id' => $object_id,
			'old_value' => $old_value,
			'new_value' => $new_value,
			'meta' => $meta ? wp_json_encode( $meta ) : null,
			'reverted' => 0,
			'created_at' => current_time( 'mysql' ),
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
