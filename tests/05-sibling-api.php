<?php

add_action( 'mb_relationships_init', function () {
	MB_Relationships_API::register( array(
		'id'   => 'categories_to_posts',
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
	if ( ! is_single() ) {
		return $content;
	}
	$sibling_posts = get_posts( array(
		'taxonomy'     => 'category',
		'relationship' => array(
			'id'      => 'categories_to_posts',
			'from'    => get_the_ID(),
			'sibling' => true,
		),
	) );
	$output = '<ul>';
	foreach ( $sibling_posts as $sibling_post ) {
		$output .= '<li>' . $sibling_post->post_title . '</li>';
	}
	$output .= '</ul>';
	return $content . $output;
} );