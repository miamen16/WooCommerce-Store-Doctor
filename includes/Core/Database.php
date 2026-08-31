<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles custom table creation and versioning.
 *
 * Tables:
 * - {prefix}wcsd_issues        : every issue found by a scan (current snapshot)
 * - {prefix}wcsd_health_history: one row per completed scan, with per-category scores
 * - {prefix}wcsd_fix_backups   : one row per changed field, grouped by batch_id, so
 *                                 an Auto Fix run can be reverted later
 */
class Database {

	public static function issues_table() {
		global $wpdb;
		return $wpdb->prefix . 'wcsd_issues';
	}

	public static function history_table() {
		global $wpdb;
		return $wpdb->prefix . 'wcsd_health_history';
	}

	public static function backups_table() {
		global $wpdb;
		return $wpdb->prefix . 'wcsd_fix_backups';
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$issues_table  = self::issues_table();
		$history_table = self::history_table();

		$sql_issues = "CREATE TABLE {$issues_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			scanner VARCHAR(50) NOT NULL,
			type VARCHAR(100) NOT NULL,
			severity VARCHAR(20) NOT NULL DEFAULT 'warning',
			object_type VARCHAR(30) DEFAULT NULL,
			object_id BIGINT UNSIGNED DEFAULT NULL,
			message TEXT NOT NULL,
			fixable TINYINT(1) NOT NULL DEFAULT 0,
			fixer VARCHAR(100) DEFAULT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			meta LONGTEXT DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY scanner (scanner),
			KEY type (type),
			KEY severity (severity),
			KEY status (status),
			KEY object_lookup (object_type, object_id)
		) {$charset_collate};";

		$sql_history = "CREATE TABLE {$history_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			overall_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
			category_scores LONGTEXT DEFAULT NULL,
			issues_critical INT UNSIGNED NOT NULL DEFAULT 0,
			issues_warning INT UNSIGNED NOT NULL DEFAULT 0,
			issues_suggestion INT UNSIGNED NOT NULL DEFAULT 0,
			scanned_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY scanned_at (scanned_at)
		) {$charset_collate};";

		$backups_table = self::backups_table();

		$sql_backups = "CREATE TABLE {$backups_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			batch_id VARCHAR(40) NOT NULL,
			fixer VARCHAR(100) NOT NULL,
			issue_id BIGINT UNSIGNED DEFAULT NULL,
			object_type VARCHAR(30) NOT NULL,
			object_id BIGINT UNSIGNED NOT NULL,
			old_value LONGTEXT DEFAULT NULL,
			new_value LONGTEXT DEFAULT NULL,
			meta LONGTEXT DEFAULT NULL,
			reverted TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY batch_id (batch_id),
			KEY fixer (fixer),
			KEY object_lookup (object_type, object_id)
		) {$charset_collate};";

		dbDelta( $sql_issues );
		dbDelta( $sql_history );
		dbDelta( $sql_backups );

		update_option( 'wcsd_db_version', WCSD_DB_VERSION );
	}

	/**
	 * Call on plugins_loaded to upgrade tables if the plugin was updated.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'wcsd_db_version' ) !== WCSD_DB_VERSION ) {
			self::install();
		}
	}
}
