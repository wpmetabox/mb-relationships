<?php
/**
 * The user object that handle query arguments for "to" and list for "from" relationships.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The user object.
 */
class MB_Relationships_User implements MB_Relationships_Object_Interface {
	/**
	 * Get meta box settings.
	 *
	 * @param array $args Relationship settings.
	 *
	 * @return array
	 */
	public function get_meta_box_settings( $args ) {
		$settings = array(
			'type' => 'user',
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
			'type'       => 'user',
			'clone'      => true,
			'sort_clone' => true,
			'query_args' => $args['query_args'],
		);
	}

	/**
	 * Get current object ID.
	 *
	 * @return int
	 */
	public function get_current_id() {
		$user_id = false;
		$screen  = get_current_screen();
		if ( 'profile' === $screen->id ) {
			$user_id = get_current_user_id();
		} elseif ( 'user-edit' === $screen->id ) {
			$user_id = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : false;
		}

		return $user_id;
	}

	/**
	 * Get HTML link to the object.
	 *
	 * @param int $id Object ID.
	 *
	 * @return string
	 */
	public function get_link( $id ) {
		$user = get_userdata( $id );
		return '<a href="' . admin_url( 'user-edit.php?user_id=' . $id ) . '">' . esc_html( $user->display_name ) . '</a>';
	}
}
