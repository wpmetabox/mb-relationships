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
class MB_Relationships_Post implements MB_Relationships_Object_Interface {
	/**
	 * Get meta box settings.
	 *
	 * @param array $args Relationship settings.
	 *
	 * @return array
	 */
	public function get_meta_box_settings( $args ) {
		$settings = array(
			'context'    => $args['meta_box']['context'],
			'priority'   => $args['meta_box']['priority'],
			'autosave'   => $args['meta_box']['autosave'],
			'closed'     => $args['meta_box']['closed'],
			'post_types' => $args['post_type'],
		);
		return $settings;
	}

	/**
	 * Get query arguments.
	 *
	 * @param array $args Relationship settings.
	 *
	 * @return array
	 */
	public function get_field_settings( $args ) {
		return array(
			'type'       => 'post',
			'clone'      => true,
			'sort_clone' => true,
			'post_type'  => $args['post_type'],
			'query_args' => $args['query_args'],
		);
	}

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
