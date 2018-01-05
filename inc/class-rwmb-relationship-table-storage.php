<?php
/**
 * Custom table storage
 *
 * @package    Meta Box
 * @subpackage MB Custom Table
 */

if ( interface_exists( 'RWMB_Storage_Interface' ) ) {
	/**
	 * Class RWMB_Table_Storage
	 */
	class RWMB_Relationship_Table_Storage implements RWMB_Storage_Interface {
		/**
		 * Table name.
		 *
		 * @var string
		 */
		protected $table;

		/**
		 * WPDB object.
		 *
		 * @var wpdb
		 */
		protected $db;

		/**
		 * RWMB_Table_Storage constructor.
		 */
		public function __construct() {
			global $wpdb;
			$this->db = $wpdb;
		}

		/**
		 * Set the table name.
		 *
		 * @param string $table Table name.
		 */
		public function set_table( $table ) {
			$this->table = $table;
		}

		/**
		 * Retrieve metadata for the specified object.
		 *
		 * @param int        $object_id ID of the object metadata is for. In this case, it will be a row's id
		 *                              of table.
		 * @param string     $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
		 *                              the specified object. In this case, it will be column name.
		 * @param bool|array $args      Optional, default is false.
		 *                              If true, return only the first value of the specified meta_key.
		 *                              If is array, use the `single` element.
		 *                              This parameter has no effect if meta_key is not specified.
		 *
		 * @return mixed Single metadata value, or array of values.
		 */
		public function get( $object_id, $meta_key, $args = false ) {
			return $this->db->get_col( $this->db->prepare(
				"SELECT `to` FROM {$this->table} WHERE `from`=%d",
				$object_id
			) );
		}

		/**
		 * Add metadata to cache
		 *
		 * @param int    $object_id  ID of the object metadata is for.
		 * @param string $meta_key   Metadata key.
		 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
		 * @param bool   $unique     Optional, default is false.
		 *                           Whether the specified metadata key should be unique for the object.
		 *                           If true, and the object already has a value for the specified metadata key,
		 *                           no change will be made.
		 */
		public function add( $object_id, $meta_key, $meta_value, $unique = false ) {
		}

		/**
		 * Update object relationship.
		 *
		 * @param int    $object_id  ID of the object metadata is for.
		 * @param string $meta_key   Metadata key.
		 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
		 * @param mixed  $prev_value Optional. If specified, only update existing metadata entries with
		 *                           the specified value. Otherwise, update all entries.
		 *
		 * @return bool
		 */
		public function update( $object_id, $meta_key, $meta_value, $prev_value = '' ) {
			$this->delete( $object_id );
			$meta_value = array_filter( (array) $meta_value );
			foreach ( $meta_value as $value ) {
				$this->db->insert(
					$this->table,
					array(
						'from' => $object_id,
						'to'   => $value,
					),
					array(
						'%d',
						'%d',
					)
				);
			}
			return true;
		}

		/**
		 * Delete object relationship.
		 *
		 * @param int    $object_id  ID of the object metadata is for.
		 * @param string $meta_key   Metadata key. If empty, delete row.
		 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if non-scalar. If specified, only delete
		 *                           metadata entries with this value. Otherwise, delete all entries with the specified meta_key.
		 *                           Pass `null, `false`, or an empty string to skip this check. (For backward compatibility,
		 *                           it is not possible to pass an empty string to delete those entries with an empty string
		 *                           for a value).
		 * @param bool   $delete_all Optional, default is false. If true, delete matching metadata entries for all objects,
		 *                           ignoring the specified object_id. Otherwise, only delete matching metadata entries for
		 *                           the specified object_id.
		 *
		 * @return bool True on successful delete, false on failure.
		 */
		public function delete( $object_id, $meta_key = '', $meta_value = '', $delete_all = false ) {
			$this->db->delete( $this->table, array(
				'from' => $object_id,
			) );
			return true;
		}
	}
}
