<?php
/**
 * Create tables for the plugin.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The tables class
 */
class MB_Relationships_Table {
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

		// Register new table.
		$this->db->tables[]         = 'mb_relationships';
		$this->db->mb_relationships = $this->db->prefix . 'mb_relationships';
	}

	/**
	 * Create shared table for all relationships.
	 */
	public function create() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Create new table.
		$sql = "
			CREATE TABLE {$this->db->mb_relationships} (
				`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`from` bigint(20) unsigned NOT NULL,
				`to` bigint(20) unsigned NOT NULL,
				`type` varchar(44) NOT NULL default '',
				PRIMARY KEY  (`ID`),
				KEY `from` (`from`),
				KEY `to` (`to`),
				KEY `type` (`type`)
			) COLLATE {$this->db->collate};
		";
		dbDelta( $sql );
	}
}
