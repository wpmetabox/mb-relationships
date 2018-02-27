<?php
/**
 * The interface for objects (posts, terms, users, etc.).
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Object interface.
 */
interface MB_Relationships_Object_Interface {
	/**
	 * Get meta box settings.
	 *
	 * @param array $args Relationship settings.
	 *
	 * @return array
	 */
	public function get_meta_box_settings( $args );

	/**
	 * Get query arguments.
	 *
	 * @param array $args Relationship settings.
	 *
	 * @return array
	 */
	public function get_field_settings( $args );

	/**
	 * Get current object ID in the admin area.
	 *
	 * @return int
	 */
	public function get_current_admin_id();

	/**
	 * Get current object ID.
	 *
	 * @return int
	 */
	public function get_current_id();

	/**
	 * Render HTML of the object to show in the frontend.
	 *
	 * @param mixed $item The object.
	 *
	 * @return string
	 */
	public function render( $item );

	/**
	 * Get HTML link to the object.
	 *
	 * @param int $id Object ID.
	 *
	 * @return string
	 */
	public function get_link( $id );

	/**
	 * Get database ID field.
	 *
	 * @return string
	 */
	public function get_db_field();
}
