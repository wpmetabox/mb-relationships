<?php
/**
 * This test creates 2 relationships: from posts to pages and from posts to posts.
 * Then it queries for connected items from the current post and belong to either the relationship.
 */
add_action(
	'mb_relationships_init',
	function () {
		MB_Relationships_API::register(
			array(
				'id'   => 'posts_to_pages',
				'from' => array(
					'object_type' => 'post',
					'post_type'   => 'post',
					'meta_box'    => array(
						'title' => 'Posts 2 Pages Connects To',
						'field_title' => 'Select Posts',
					),
				),
				'to' => array(
					'object_type' => 'post',
					'post_type'   => 'page',
					'meta_box'    => array(
						'title' => 'Posts 2 Pages Connects From',
					),
				),
			)
		);
		MB_Relationships_API::register(
			array(
				'id'   => 'posts_to_posts',
				'from' => array(
					'object_type' => 'post',
					'post_type'   => 'post',
					'meta_box'    => array(
						'title' => 'Posts 2 Posts Connects To',
						'field_title' => 'Select Posts',
					),
				),
				'to' => array(
					'object_type' => 'post',
					'post_type'   => 'post',
					'meta_box'    => array(
						'title' => 'Posts 2 Posts Connects From',
					),
				),
			)
		);
	}
);
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_single() ) {
			return $content;
		}
		$related = new WP_Query(
			array(
				'relationship' => array(
					'relation' => 'OR',
					array(
						'id'   => 'posts_to_pages',
						'from' => get_the_ID(),
					),
					array(
						'id'   => 'posts_to_posts',
						'from' => get_the_ID(),
					),
				),
				'nopaging'     => true,
			)
		);
		$output  = '<ul>';
		while ( $related->have_posts() ) {
			$related->the_post();
			$output .= '<li>' . get_the_title() . '</li>';
		}
		wp_reset_postdata();
		$output .= '</ul>';
		return $content . $output;
	}
);
