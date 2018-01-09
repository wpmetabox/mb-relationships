<?php
/**
 * The post object that handle query arguments for "to" and list for "from" connections.
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

/**
 * The post object.
 */
class MB_Relationship_Post implements MB_Relationship_Object_Interface {
	/**
	 * Get query arguments.
	 *
	 * @param array $settings Connection settings.
	 *
	 * @return array
	 */
	public function get_query_args( $settings ) {
		return array(
			'type'       => 'post',
			'clone'      => true,
			'sort_clone' => true,
			'post_type'  => $settings['post_type'],
			'query_args' => $settings['query_args'],
		);
	}

	/**
	 * Get current object ID.
	 *
	 * @return int
	 */
	public function get_current_id() {
		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $post_id ) {
			$post_id = filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT );
		}
		return is_numeric( $post_id ) ? absint( $post_id ) : false;
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
}
