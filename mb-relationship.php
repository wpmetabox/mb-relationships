<?php
/**
 * Plugin Name: MB Relationship
 * Plugin URI: https://metabox.io/plugins/mb-relationship/
 * Description: Create many-to-many relationships between posts, users, terms, etc.
 * Version: 1.0.0
 * Author: MetaBox.io
 * Author URI: https://metabox.io
 * License: GPL2+
 * Text Domain: mb-relationship
 * Domain Path: /languages/
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

// Prevent loading this file directly.
defined( 'ABSPATH' ) || exit;

if ( function_exists( 'mb_relationship_load' ) ) {
	return;
}


// Hook to 'init' with priority 5 to make sure all actions are registered before Meta Box runs.
add_action( 'init', 'mb_relationship_load', 5 );

/**
 * Load plugin files after Meta Box is loaded.
 */
function mb_custom_table_load() {
	if ( ! defined( 'RWMB_VER' ) || class_exists( 'MB_Relationship_Table' ) ) {
		return;
	}
	require_once dirname( __FILE__ ) . '/inc/class-mb-relationship-table.php';

	global $wpdb;
	$table = new MB_Relationship_Table( $wpdb );
	$table->create_shared();
}
