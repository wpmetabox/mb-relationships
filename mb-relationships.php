<?php
/**
 * Plugin Name: MB Relationships
 * Plugin URI: https://metabox.io/plugins/mb-relationships/
 * Description: Create many-to-many relationships between posts, users, terms, etc.
 * Version: 1.0.0
 * Author: MetaBox.io
 * Author URI: https://metabox.io
 * License: GPL2+
 * Text Domain: mb-relationships
 * Domain Path: /languages/
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

// Prevent loading this file directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mb_relationships_load' ) ) {
	// Hook to 'init' with priority 5 to make sure all actions are registered before Meta Box runs.
	add_action( 'init', 'mb_relationships_load', 5 );
	/**
	 * Load plugin files after Meta Box is loaded.
	 */
	function mb_relationships_load() {
		if ( ! defined( 'RWMB_VER' ) || class_exists( 'MB_Relationships_Table' ) ) {
			return;
		}
		require_once dirname( __FILE__ ) . '/inc/database/class-mb-relationships-table.php';
		require_once dirname( __FILE__ ) . '/inc/database/class-rwmb-relationships-table-storage.php';
		require_once dirname( __FILE__ ) . '/inc/database/class-mb-relationships-storage-handler.php';

		require_once dirname( __FILE__ ) . '/inc/object/class-mb-relationships-object-interface.php';
		require_once dirname( __FILE__ ) . '/inc/object/class-mb-relationships-post.php';
		require_once dirname( __FILE__ ) . '/inc/object/class-mb-relationships-term.php';
		require_once dirname( __FILE__ ) . '/inc/object/class-mb-relationships-user.php';
		require_once dirname( __FILE__ ) . '/inc/object/class-mb-relationships-object-factory.php';

		require_once dirname( __FILE__ ) . '/inc/class-mb-relationships-relationship-factory.php';
		require_once dirname( __FILE__ ) . '/inc/class-mb-relationships-relationship.php';

		require_once dirname( __FILE__ ) . '/inc/class-mb-relationships-api.php';

		do_action( 'mb_relationships_pre_init' );

		global $wpdb;
		$table = new MB_Relationships_Table( $wpdb );
		$table->create();

		$object_factory       = new MB_Relationships_Object_Factory();
		$relationship_factory = new MB_Relationships_Relationship_Factory( $object_factory );

		$storage_handler = new MB_Relationships_Storage_Handler( $relationship_factory );
		$storage_handler->init();

		$api = new MB_Relationships_API( $wpdb, $relationship_factory );

		// All registration code goes here.
		do_action( 'mb_relationships_init', $api );
	}
}
