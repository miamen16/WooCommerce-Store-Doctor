<?php
/**
 * Fires only when the user deletes the plugin from wp-admin (not on deactivate).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wcsd_tables = array(
	$wpdb->prefix . 'wcsd_issues',
	$wpdb->prefix . 'wcsd_health_history',
	$wpdb->prefix . 'wcsd_fix_backups',
);

foreach ( $wcsd_tables as $wcsd_table ) {
	// Table names are generated from the trusted WordPress database prefix.
	$wpdb->query( "DROP TABLE IF EXISTS {$wcsd_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
}

delete_option( 'wcsd_db_version' );
delete_option( 'wcsd_last_scan' );
