<?php
/**
 * The post object that handle query arguments for "to" and list for "from" relationships.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The post object.
 */
class MBR_Post implements MBR_Object_Interface {
	/**
	 * Get current object ID.
	 *
	 * @return int
	 */
	public function get_current_admin_id() {
		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $post_id ) {
			$post_id = filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT );
		}
		return is_numeric( $post_id ) ? absint( $post_id ) : false;
	}

	/**
	 * Get current object ID.
	 *
	 * @return int
	 */
	public function get_current_id() {
		return get_the_ID();
	}

	/**
	 * Get HTML link to the object.
	 *
	 * @param int $id Object ID.
	 *
	 * @return string
	 */
	public function get_link( $id ) {
		return '<a href="' . get_edit_post_link( $id ) . '">' . get_the_title( $id ) . '</a>';
	}

	/**
	 * Render HTML of the object to show in the frontend.
	 *
	 * @param WP_Post $item Post object.
	 *
	 * @return string
	 */
	public function render( $item ) {
		return '<a href="' . get_permalink( $item ) . '">' . get_the_title( $item ) . '</a>';
	}

	/**
	 * Get database ID field.
	 *
	 * @return string
	 */
	public function get_db_field() {
		return 'ID';
	}
}
