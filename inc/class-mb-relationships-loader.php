<?php
/**
 * Plugin loader.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The loader class.
 */
class MB_Relationships_Loader {
	/**
	 * Detect if the relationships table is created.
	 *
	 * @var bool
	 */
	protected $is_table_created = false;

	/**
	 * Plugin activation.
	 */
	public function activate() {
		$this->create_table();
		$this->is_table_created = true;
	}

	/**
	 * Initialization.
	 */
	public function init() {
		if ( ! defined( 'RWMB_VER' ) ) {
			return;
		}

		$this->load_files();

		/**
		 * If plugin is embed in another plugin, the table is not created during activation.
		 * Thus, we have to create it while initializing.
		 */
		if ( ! $this->is_table_created ) {
			$this->create_table();
		}

		$obj_factory = new MB_Relationships_Object_Factory();
		$rel_factory = new MB_Relationships_Relationship_Factory( $obj_factory );

		$storage_handler = new MB_Relationships_Storage_Handler( $rel_factory );
		$storage_handler->init();

		$normalizer = new MB_Relationships_Query_Normalizer( $rel_factory );
		$post_query = new MB_Relationships_Query_Post( $normalizer );
		$post_query->init();
		$term_query = new MB_Relationships_Query_Term( $normalizer );
		$term_query->init();
		$user_query = new MB_Relationships_Query_User( $normalizer );
		$user_query->init();

		MB_Relationships_API::set_relationship_factory( $rel_factory );
		MB_Relationships_API::set_post_query( $post_query );
		MB_Relationships_API::set_term_query( $term_query );
		MB_Relationships_API::set_user_query( $user_query );

		$shortcodes = new MB_Relationships_Shortcodes( $rel_factory, $obj_factory );
		$shortcodes->init();

		// All registration code goes here.
		do_action( 'mb_relationships_init' );
	}

	/**
	 * Create relationships table.
	 */
	protected function create_table() {
		require 'database/class-mb-relationships-table.php';

		$table = new MB_Relationships_Table();
		$table->create();
	}

	/**
	 * Load plugin files.
	 */
	protected function load_files() {
		require 'database/class-rwmb-relationships-table-storage.php';
		require 'database/class-mb-relationships-storage-handler.php';

		require 'object/class-mb-relationships-object-interface.php';
		require 'object/class-mb-relationships-post.php';
		require 'object/class-mb-relationships-term.php';
		require 'object/class-mb-relationships-user.php';
		require 'object/class-mb-relationships-object-factory.php';

		require 'query/class-mb-relationships-query.php';
		require 'query/class-mb-relationships-query-normalizer.php';
		require 'query/class-mb-relationships-query-post.php';
		require 'query/class-mb-relationships-query-term.php';
		require 'query/class-mb-relationships-query-user.php';

		require 'class-mb-relationships-relationship-factory.php';
		require 'class-mb-relationships-relationship.php';

		require 'class-mb-relationships-api.php';
		require 'class-mb-relationships-shortcodes.php';
	}
}
