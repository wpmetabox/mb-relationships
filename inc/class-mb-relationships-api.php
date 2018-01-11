<?php
/**
 * Public API helper functions.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The API class.
 */
class MB_Relationships_API {
	/**
	 * The reference to WordPress global database object.
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * Reference to relationship factory.
	 *
	 * @var MB_Relationships_Relationship_Factory
	 */
	public $factory;

	/**
	 * Constructor
	 *
	 * @param wpdb                                  $wpdb    Database object.
	 * @param MB_Relationships_Relationship_Factory $factory The object factory.
	 */
	public function __construct( wpdb $wpdb, MB_Relationships_Relationship_Factory $factory ) {
		$this->db      = $wpdb;
		$this->factory = $factory;
	}

	/**
	 * Register a relationship.
	 *
	 * @param array $settings Relationship parameters.
	 *
	 * @return MB_Relationships_Relationship
	 */
	public function register( $settings ) {
		return $this->factory->build( $settings );
	}

	/**
	 * Get connected items from an item.
	 *
	 * @param string $type      Relationship type.
	 * @param int    $object_id Object ID. Optional.
	 *
	 * @return array
	 */
	public function get_connected_from( $type, $object_id = null ) {
		$object_id = empty( $object_id ) ? get_queried_object_id() : $object_id;
		return $this->db->get_col( $this->db->prepare(
			"SELECT `to` FROM {$this->db->mb_relationships} WHERE `from`=%d AND `type`=%s",
			$object_id,
			$type
		) );
	}

	/**
	 * Get connected items to an item.
	 *
	 * @param string $type      Relationship type.
	 * @param int    $object_id Object ID. Optional.
	 *
	 * @return array
	 */
	public function get_connected_to( $type, $object_id = null ) {
		$object_id = empty( $object_id ) ? get_queried_object_id() : $object_id;
		return $this->db->get_col( $this->db->prepare(
			"SELECT `from` FROM {$this->db->mb_relationships} WHERE `to`=%d AND `type`=%s",
			$object_id,
			$type
		) );
	}
}
