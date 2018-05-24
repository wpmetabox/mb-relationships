<?php
add_action( 'mb_relationships_init', function () {
	MB_Relationships_API::register( array(
		'id'   => 'users_to_posts',
		'from' => array(
			'object_type' => 'user',
			'meta_box'    => array(
				'title'       => 'Manages',
				'field_title' => 'Select Posts',
			),
		),
		'to'   => array(
			'object_type' => 'post',
			'post_type'   => 'post',
			'meta_box'    => array(
				'title'         => 'Managed By',
				'context'       => 'side',
				'empty_message' => 'No users',
			),
		),
	) );
} );
add_filter( 'the_content', function ( $content ) {
	if ( ! is_single() ) {
		return $content;
	}
	$users  = get_users( array(
		'relationship' => array(
			'id' => 'users_to_posts',
			'to' => get_the_ID(),
		),
	) );
	$output = '<ul>';
	foreach ( $users as $user ) {
		$output .= '<li>' . $user->display_name . '</li>';
	}
	$output .= '</ul>';
	return $content . $output;
} );
