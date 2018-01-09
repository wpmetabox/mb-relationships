<?php
/**
 * Public API helper functions.
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

/**
 * The API class.
 */
class MB_Relationship_API {
	/**
	 * The reference to WordPress global database object.
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * The relationship table name.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Reference to object factory.
	 *
	 * @var MB_Relationship_Object_Factory
	 */
	public $factory;

	/**
	 * Constructor
	 *
	 * @param wpdb                           $wpdb    Database object.
	 * @param MB_Relationship_Object_Factory $factory The object factory.
	 */
	public function __construct( wpdb $wpdb, MB_Relationship_Object_Factory $factory ) {
		$this->db      = $wpdb;
		$this->table   = MB_Relationship_Table::get_shared_name();
		$this->factory = $factory;
	}

	/**
	 * Register a relationship type.
	 *
	 * @param array $args Relationship parameters.
	 *
	 * @return MB_Relationship_Type
	 */
	public function register( $args ) {
		return new MB_Relationship_Type( $args, $this );
	}

	/**
	 * Get connected items from an item.
	 *
	 * @param string $type      Connection type.
	 * @param int    $object_id Object ID. Optional.
	 *
	 * @return array
	 */
	public function get_connected_from( $type, $object_id = null ) {
		$object_id = empty( $object_id ) ? get_queried_object_id() : $object_id;
		return $this->db->get_col( $this->db->prepare(
			"SELECT `to` FROM {$this->table} WHERE `from`='%d' AND `type`='%s'",
			$object_id,
			$type
		) );
	}

	/**
	 * Get connected items to an item.
	 *
	 * @param string $type      Connection type.
	 * @param int    $object_id Object ID. Optional.
	 *
	 * @return array
	 */
	public function get_connected_to( $type, $object_id = null ) {
		$object_id = empty( $object_id ) ? get_queried_object_id() : $object_id;
		return $this->db->get_col( $this->db->prepare(
			"SELECT `from` FROM {$this->table} WHERE `to`='%d' AND `type`='%s'",
			$object_id,
			$type
		) );
	}
}
