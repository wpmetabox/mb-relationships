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
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$table = self::get_shared_name();
		$sql   = "
			CREATE TABLE {$table} (
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

	/**
	 * Get the shared table name.
	 *
	 * @return string
	 */
	public static function get_shared_name() {
		global $wpdb;
		return $wpdb->prefix . 'mb_relationship';
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
