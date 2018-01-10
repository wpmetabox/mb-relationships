<?php
/**
 * Storage handler, which sets the correct storage for meta box objects.
 *
 * @package    Meta Box
 * @subpackage MB Custom Table
 */

/**
 * Storage handler class.
 */
class MB_Relationship_Storage_Handler {
	/**
	 * Reference to connection factory.
	 *
	 * @var MB_Relationship_Connection_Factory
	 */
	protected $factory;

	/**
	 * The storage object for relationship table.
	 *
	 * @var RWMB_Storage_Interface
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @param MB_Relationship_Connection_Factory $factory Reference to connection factory.
	 */
	public function __construct( MB_Relationship_Connection_Factory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Class initialize.
	 */
	public function init() {
		add_filter( 'rwmb_get_storage', array( $this, 'filter_storage' ), 10, 3 );
		add_action( 'deleted_post', array( $this, 'delete_object_data' ) );
		add_action( 'deleted_user', array( $this, 'delete_object_data' ) );
		add_action( 'delete_term', array( $this, 'delete_object_data' ) );
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
		if ( ! $meta_box || ! $this->is_relationship( $meta_box ) ) {
			return $storage;
		}
		if ( ! $this->storage ) {
			$this->storage = new RWMB_Relationship_Table_Storage();
			$this->storage->set_table( $this->storage->db->mb_relationships );
		}

		return $this->storage;
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
	 * Delete object data in cache and in the database.
	 *
	 * @param int $object_id Object ID.
	 */
	public function delete_object_data( $object_id ) {
		$connections = $this->factory->get_by( array(
			'object_type' => str_replace( array( 'deleted_', 'delete_' ), '', current_filter() ),
		) );
		foreach ( $connections as $connection ) {
			$this->delete_object_connections( $object_id, $connection->id );
		}
	}

	/**
	 * Delete all connections to an object.
	 *
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $type      The connection type.
	 */
	protected function delete_object_connections( $object_id, $type ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM $wpdb->mb_relationships WHERE `type`=%s AND (`from`=%d OR `to`=%d)",
			$type,
			$object_id,
			$object_id
		) );
	}
}
