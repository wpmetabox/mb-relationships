<?php
/**
 * The term object that handle query arguments for "to" and list for "from" relationships.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The term object.
 */
class MB_Relationships_Term implements MB_Relationships_Object_Interface {
	/**
	 * Get meta box settings.
	 *
	 * @param array $args Relationship settings.
	 *
	 * @return array
	 */
	public function get_meta_box_settings( $args ) {
		$settings = array(
			'taxonomies' => $args['taxonomy'],
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
			'type'       => 'taxonomy_advanced',
			'clone'      => true,
			'sort_clone' => true,
			'taxonomy'   => $args['taxonomy'],
			'query_args' => $args['query_args'],
		);
	}

	/**
	 * Get current object ID.
	 *
	 * @return int
	 */
	public function get_current_admin_id() {
		return filter_input( INPUT_GET, 'tag_ID', FILTER_SANITIZE_NUMBER_INT );
	}

	/**
	 * Get current object ID.
	 *
	 * @return int
	 */
	public function get_current_id() {
		return get_queried_object_id();
	}

	/**
	 * Get HTML link to the object.
	 *
	 * @param int $id Object ID.
	 *
	 * @return string
	 */
	public function get_link( $id ) {
		$term = get_term( $id );
		return '<a href="' . get_edit_term_link( $id ) . '">' . esc_html( $term->name ) . '</a>';
	}

	/**
	 * Render HTML of the object to show in the frontend.
	 *
	 * @param WP_Term $item Term object.
	 *
	 * @return string
	 */
	public function render( $item ) {
		return '<a href="' . get_term_link( $item ) . '">' . esc_html( $item->name ) . '</a>';
	}

	/**
	 * Get database ID field.
	 *
	 * @return string
	 */
	public function get_db_field() {
		return 'term_id';
	}
}
