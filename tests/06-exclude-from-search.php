<?php
add_action(
	'init',
	function() {
		$labels = array(
			'name'               => __( 'Plural Name', 'mb-relationships' ),
			'singular_name'      => __( 'Singular Name', 'mb-relationships' ),
			'add_new'            => _x( 'Add New Singular Name', 'mb-relationships', 'mb-relationships' ),
			'add_new_item'       => __( 'Add New Singular Name', 'mb-relationships' ),
			'edit_item'          => __( 'Edit Singular Name', 'mb-relationships' ),
			'new_item'           => __( 'New Singular Name', 'mb-relationships' ),
			'view_item'          => __( 'View Singular Name', 'mb-relationships' ),
			'search_items'       => __( 'Search Plural Name', 'mb-relationships' ),
			'not_found'          => __( 'No Plural Name found', 'mb-relationships' ),
			'not_found_in_trash' => __( 'No Plural Name found in Trash', 'mb-relationships' ),
			'parent_item_colon'  => __( 'Parent Singular Name:', 'mb-relationships' ),
			'menu_name'          => __( 'Plural Name', 'mb-relationships' ),
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => 'description',
			'taxonomies'          => array(),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => null,
			'menu_icon'           => null,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'trackbacks',
				'comments',
				'revisions',
				'page-attributes',
				'post-formats',
			),
		);
		register_post_type( 'slug', $args );
	}
);
add_action(
	'mb_relationships_init',
	function () {
		MB_Relationships_API::register(
			array(
				'id'   => 'posts_to_pages',
				'from' => 'post',
				'to'   => 'slug',
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
					'id'   => 'posts_to_pages',
					'from' => get_the_ID(),
				),
				'nopaging'     => true,
				'post_type'    => 'slug',
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
