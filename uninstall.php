<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || die;

// Allow developers to opt-out to delete custom table.
$delete_table = apply_filters( 'mb_relationships_delete_table', true );

if ( ! $delete_table ) {
	return;
}

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mb_relationships" );

delete_option( 'mbr_table_created' );
