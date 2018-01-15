<?php
add_action( 'mb_relationships_init', function () {
	MB_Relationships_API::register( array(
		'id'   => 'posts_to_pages',
		'from' => 'post',
		'to'   => 'page',
	) );
} );
add_filter( 'the_content', function ( $content ) {
	if ( ! is_single() ) {
		return $content;
	}
	$related = new WP_Query( array(
		'relationship' => array(
			'id'   => 'posts_to_pages',
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
