<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || die;

$delete_table = defined( 'MB_RELATIONSHIPS_DELETE_TABLE' ) ? MB_RELATIONSHIPS_DELETE_TABLE : false;
if ( ! $delete_table ) {
	return;
}

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mb_relationships" );

delete_option( 'mbr_table_created' );
