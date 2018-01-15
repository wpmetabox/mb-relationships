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
	$terms  = get_terms( array(
		'taxonomy'     => 'category',
		'hide_empty'   => false,
		'relationship' => array(
			'id' => 'categories_to_posts',
			'to' => get_the_ID(),
		),
	) );
	$output = '<ul>';
	foreach ( $terms as $term ) {
		$output .= '<li>' . $term->name . '</li>';
	}
	$output .= '</ul>';
	return $content . $output;
} );
