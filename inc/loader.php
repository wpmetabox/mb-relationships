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
class MBR_Loader {
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

		$obj_factory = new MBR_Object_Factory();
		$rel_factory = new MBR_Relationship_Factory( $obj_factory );

		$storage_handler = new MBR_Storage_Handler( $rel_factory );
		$storage_handler->init();

		$normalizer = new MBR_Query_Normalizer( $rel_factory );
		$post_query = new MBR_Query_Post( $normalizer );
		$post_query->init();
		$term_query = new MBR_Query_Term( $normalizer );
		$term_query->init();
		$user_query = new MBR_Query_User( $normalizer );
		$user_query->init();

		MB_Relationships_API::set_relationship_factory( $rel_factory );
		MB_Relationships_API::set_post_query( $post_query );
		MB_Relationships_API::set_term_query( $term_query );
		MB_Relationships_API::set_user_query( $user_query );

		$shortcodes = new MBR_Shortcodes( $rel_factory, $obj_factory );
		$shortcodes->init();

		// All registration code goes here.
		do_action( 'mb_relationships_init' );
	}

	/**
	 * Create relationships table.
	 */
	protected function create_table() {
		require 'database/table.php';

		$table = new MBR_Table();
		$table->create();
	}

	/**
	 * Load plugin files.
	 */
	protected function load_files() {
		require 'database/storage.php';
		require 'database/storage-handler.php';

		require 'object/interface.php';
		require 'object/post.php';
		require 'object/term.php';
		require 'object/user.php';
		require 'object/factory.php';

		require 'query/query.php';
		require 'query/normalizer.php';
		require 'query/post.php';
		require 'query/term.php';
		require 'query/user.php';

		require 'relationship/factory.php';
		require 'relationship/relationship.php';
		require 'relationship/admin-columns.php';
		require 'relationship/meta-boxes.php';

		require 'api.php';
		require 'shortcodes.php';
	}
}
