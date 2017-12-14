<?php
/**
 * Create tables for the plugin.
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

/**
 * The tables class
 */
class MB_Relationship_Table {
	/**
	 * Store the global database connector.
	 *
	 * @var wpdb
	 */
	private $db;

	/**
	 * Constructor.
	 *
	 * @param wpdb $wpdb The WordPress global database connector.
	 */
	public function __construct( $wpdb ) {
		$this->db = $wpdb;
	}

	/**
	 * Create shared table for all relationships.
	 */
	public function create_shared() {
		if ( ! $this->is_shared() ) {
			return;
		}
		$table = $this->db->prefix . 'mb_relationship';
		MB_Custom_Table_API::create( $table, array(
			'from' => 'bigint(20) unsigned NOT NULL',
			'to'   => 'bigint(20) unsigned NOT NULL',
			'type' => 'varchar(44) NOT NULL default \'\'',
		), array( 'from', 'to', 'type' ) );
	}

	/**
	 * Helper function to create a custom table with optional column for a single relationship.
	 *
	 * @param string $name    Table name.
	 * @param array  $columns Custom columns for meta data. Optional.
	 * @param array  $keys    Key index for custom columns. Optional.
	 */
	public static function create( $name, $columns, $keys ) {
		$columns = array_merge( array(
			'from' => 'bigint(20) unsigned NOT NULL',
			'to'   => 'bigint(20) unsigned NOT NULL',
		), $columns );
		$keys    = array_merge( array( 'from', 'to' ), $keys );
		MB_Custom_Table_API::create( $name, $columns, $keys );
	}

	/**
	 * Check if relationship tables are shared.
	 *
	 * @return bool
	 */
	private function is_shared() {
		return apply_filters( 'mb_relationship_shared', true );
	}
}
