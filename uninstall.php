<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || die;

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mb_relationships" );
