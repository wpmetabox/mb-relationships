<?php
/**
 * The term object that handle query arguments for "to" and list for "from" connections.
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

/**
 * The term object.
 */
class MB_Relationship_Term implements MB_Relationship_Object_Interface {
	/**
	 * Get query arguments.
	 *
	 * @param array $settings Connection settings.
	 *
	 * @return array
	 */
	public function get_query_args( $settings ) {
		return array(
			'type'       => 'taxonomy_advanced',
			'clone'      => true,
			'sort_clone' => true,
			'taxonomy'   => $settings['taxonomy'],
			'query_args' => $settings['query_args'],
		);
	}

	/**
	 * Get current object ID.
	 *
	 * @return int
	 */
	public function get_current_id() {
		return filter_input( INPUT_GET, 'tag_ID', FILTER_SANITIZE_NUMBER_INT );
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
}
