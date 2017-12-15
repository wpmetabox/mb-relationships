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
	protected $db;

	/**
	 * The table name for shared relationships.
	 *
	 * @var string
	 */
	protected $shared_table_name = 'mb_relationship';

	/**
	 * Constructor.
	 *
	 * @param wpdb $wpdb The WordPress global database connector.
	 */
	public function __construct( $wpdb ) {
		$this->db = $wpdb;
		$this->shared_table_name = $this->db->prefix . $this->shared_table_name;
	}

	/**
	 * Create shared table for all relationships.
	 */
	public function create_shared() {
		if ( ! $this->is_shared() ) {
			return;
		}
		MB_Custom_Table_API::create( $this->shared_table_name, array(
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
	 * Get the shared table name.
	 *
	 * @return string
	 */
	public function get_shared_table_name() {
		return $this->shared_table_name;
	}

	/**
	 * Check if relationship tables are shared.
	 *
	 * @return bool
	 */
	protected function is_shared() {
		return apply_filters( 'mb_relationship_shared', true );
	}
}
