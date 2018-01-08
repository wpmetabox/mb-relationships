<?php
/**
 * Loader class
 *
 * @package    Meta Box
 * @subpackage MB Custom Table
 */

/**
 * Loader class.
 */
class MB_Relationship_Loader {
	/**
	 * Class initialize.
	 */
	public function init() {
		add_filter( 'rwmb_get_storage', array( $this, 'filter_storage' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'delete_post_data' ) );
		add_action( 'delete_term', array( $this, 'delete_term_data' ) );
		add_action( 'deleted_user', array( $this, 'delete_user_data' ) );
	}

	/**
	 * Filter storage object.
	 *
	 * @param RWMB_Storage_Interface $storage     Storage object.
	 * @param string                 $object_type Object type.
	 * @param RW_Meta_Box            $meta_box    Meta box object.
	 *
	 * @return mixed
	 */
	public function filter_storage( $storage, $object_type, $meta_box ) {
		static $relationship_storage = null;
		if ( ! $meta_box || $this->is_relationship( $meta_box ) ) {
			return $storage;
		}
		if ( null === $relationship_storage ) {
			$relationship_storage = new RWMB_Relationship_Table_Storage();
			$relationship_storage->set_table( MB_Relationship_Table::get_shared_name() );
		}

		$storage = $relationship_storage;

		return $storage;
	}

	/**
	 * Check if meta box is relationship.
	 *
	 * @param RW_Meta_Box $meta_box Meta box object.
	 *
	 * @return bool
	 */
	protected function is_relationship( $meta_box ) {
		return 'relationship_table' === $meta_box->storage_type;
	}

	/**
	 * Removes custom table data for post when delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function delete_post_data( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( 'revision' === $post_type ) {
			return;
		}
		$this->delete_object_data( $post_id, array(
			'object_type' => 'post',
		) );
	}

	/**
	 * Removes custom table data for term when delete.
	 *
	 * @param int $term_id Term ID.
	 */
	public function delete_term_data( $term_id ) {
		$this->delete_object_data( $term_id, array(
			'object_type' => 'term',
		) );
	}

	/**
	 * Removes custom table data for user when delete.
	 *
	 * @param int $user_id User ID.
	 */
	public function delete_user_data( $user_id ) {
		$this->delete_object_data( $user_id, array(
			'object_type' => 'user',
		) );
	}

	/**
	 * Delete object data in cache and in the database.
	 *
	 * @param int   $object_id Object ID.
	 * @param array $args      Arguments to get meta boxes for the object.
	 */
	protected function delete_object_data( $object_id, $args ) {
		$args       = wp_parse_args( $args, array(
			'storage_type' => 'custom_table',
		) );
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( $args );
		foreach ( $meta_boxes as $meta_box ) {
			$storage = $meta_box->get_storage();
			$storage->delete( $object_id ); // Delete from cache.
			$storage->delete_row( $object_id ); // Delete from DB.
		}
	}
}
