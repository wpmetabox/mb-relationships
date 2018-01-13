<?php
add_action( 'mb_relationships_init', function ( MB_Relationships_API $api ) {
	$api->register( array(
		'id'   => 'id0',
		'from' => 'post',
		'to'   => 'page',
	) );
	$api->register( array(
		'id'   => 'id2',
		'from' => array(
			'object_type' => 'user',
			'meta_box'    => array(
				'label'       => 'Manages',
				'field_title' => 'Select Posts',
			),
		),
		'to'   => array(
			'object_type' => 'post',
			'post_type'   => 'post',
			'meta_box'    => array(
				'label'         => 'Managed By',
				'context'       => 'side',
				'empty_message' => 'No users',
			),
		),
	) );

	$api->register( array(
		'id'   => 'id3',
		'from' => array(
			'object_type' => 'term',
			'taxonomy'    => 'category',
		),
		'to'   => array(
			'object_type' => 'post',
		),
	) );
} );
add_filter( 'the_content', function ( $content ) {
//	$connected = new WP_Query( array(
//		'connected_type'  => 'posts_to_pages',
//		'connected_items' => get_queried_object(),
//	) );
//
//	$output = '<ul>';
//	while ( $connected->have_posts() ) {
//		$connected->the_post();
//		$output .= '<li>' . get_the_title() . '</li>';
//	}
//	wp_reset_postdata();
//	$output .= '</ul>';
//	return $content . $output;

	$terms  = get_terms( array(
		'taxonomy'     => 'category',
		'hide_empty'   => false,
		'relationship' => array(
			'id' => 'id3',
			'to' => get_the_ID(),
		),
	) );
	$output = '<ul>';
	foreach ( $terms as $term ) {
		$output .= '<li>' . $term->name . '</li>';
	}
	$output .= '</ul>';
	return $content . $output;

//	$users  = get_users( array(
//		'relationship' => array(
//			'id' => 'id2',
//			'to' => get_the_ID(),
//		),
//	) );
//	$output = '<ul>';
//	foreach ( $users as $user ) {
//		$output .= '<li>' . $user->display_name . '</li>';
//	}
//	$output .= '</ul>';
//	return $content . $output;


	$related = new WP_Query( array(
		'relationship' => array(
			'id'   => 'id0',
			'from' => get_the_ID(),
		),
		'nopaging'     => true,
	) );
	$output  = '<ul>';
	while ( $related->have_posts() ) {
		$related->the_post();
		$output .= '<li>' . get_the_title() . '</li>';
	}
	wp_reset_postdata();
	$output .= '</ul>';
	return $content . $output;
} );

function prefix_register_relationships() {
	$args = array(
		'id'   => 'id0',
		'from' => 'post',
		'to'   => 'page',
	);
	$args = array(
		'id'   => 'id1',
		'from' => array(
			'object_type' => 'post',
			'post_type'   => 'post',
			'label'       => 'Connected From',
		),
		'to'   => array(
			'object_type' => 'post',
			'post_type'   => 'page',
			'label'       => 'Connected To',
		),
	);
	$args = array(
		'id'   => 'id2',
		'from' => array(
			'object_type' => 'user',
			'label'       => 'Managed By',
		),
		'to'   => array(
			'object_type' => 'post',
			'post_type'   => 'page',
			'label'       => 'Manages',
		),
	);
	$args = array(
		'id'        => 'id2',
		'admin_box' => array(
			'show'    => 'any', // 'any', 'from', 'to'.
			'context' => 'side',
		),
		'from'      => array(
			'object_type' => 'taxonomy',
			'taxonomy'    => 'country',
			'label'       => 'Managed By',
			'labels'      => array(
				'singular_name' => __( 'Person', 'my-textdomain' ),
				'search_items'  => __( 'Search people', 'my-textdomain' ),
				'not_found'     => __( 'No people found.', 'my-textdomain' ),
				'create'        => __( 'Create Connections', 'my-textdomain' ),
			),
		),
		'to'        => array(
			'object_type' => 'post',
			'post_type'   => 'page',
			'label'       => 'Manages',
		),
	);

	$meta_boxes[] = array(
		'title'         => 'Test custom table',
		'relationships' => 'id2',
		'fields'        => array(),
	);
}
