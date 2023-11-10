<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || die;

$delete_data = defined( 'MB_RELATIONSHIPS_DELETE_DATA' ) ? MB_RELATIONSHIPS_DELETE_DATA : false;
if ( ! $delete_data ) {
	return;
}

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mb_relationships" );

delete_option( 'mbr_table_created' );
