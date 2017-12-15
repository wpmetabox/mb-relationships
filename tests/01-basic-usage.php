<?php
add_action( 'mb_relationship_init', function () {
	MB_Relationship_API::register( array(
		'id'    => 'id0',
		'from'  => 'post',
		'to'    => 'page',
	) );
} );
function prefix_register_relationship() {
	$args = array(
		'id'    => 'id0',
		'table' => 'table_name',
		'from'  => 'post',
		'to'    => 'page',
	);
	$args = array(
		'id'    => 'id1',
		'table' => 'table_name',
		'from'  => array(
			'object_type' => 'post',
			'post_type'   => 'post',
			'label'       => 'Connected From',
		),
		'to'    => array(
			'object_type' => 'post',
			'post_type'   => 'page',
			'label'       => 'Connected To',
		),
	);
	$args = array(
		'id'    => 'id2',
		'table' => 'table_name',
		'from'  => array(
			'object_type' => 'user',
			'label'       => 'Managed By',
		),
		'to'    => array(
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
		'table'     => 'table_name',
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
