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
	 * Constructor.
	 *
	 * @param wpdb $wpdb The WordPress global database connector.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->db = $wpdb;
	}

	/**
	 * Create shared table for all relationships.
	 */
	public function create() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Register new table.
		$name               = 'mb_relationships';
		$this->db->tables[] = $name;
		$this->db->$name    = $this->db->prefix . $name;

		// Create new table.
		$sql = "
			CREATE TABLE {$this->db->$name} (
				`ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`from` bigint(20) unsigned NOT NULL,
				`to` bigint(20) unsigned NOT NULL,
				`type` varchar(44) NOT NULL default '',
				PRIMARY KEY  (`ID`),
				KEY `from` (`from`),
				KEY `to` (`to`)
			) COLLATE {$this->db->collate};
		";
		dbDelta( $sql );
	}
}
