<?php
/**
 * MBR_Admin_Filter
 */
class MBR_Admin_Filter {

	/**
	 * MB_Relationships_Admin_Filter constructor
	 */
	public function __construct() {
		add_action( 'restrict_manage_posts', [ $this, 'add_admin_filter' ] );
		add_action( 'pre_get_posts', [ $this, 'filter' ] );
		add_action( 'wp_ajax_mb_relationships_admin_filter', [ $this, 'ajax_callback' ] );

		/** Admin hooks */
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_script' ] );
	}

	/**
	 * Add a menu to the admin panel to filter by related items
	 * if the field is set to have an admin column
	 */
	public function add_admin_filter() {
		global $post_type;
		$relationships = MB_Relationships_API::get_all_relationships();		

		foreach ( $relationships as $rel ) {

			if ( isset( $rel->from['admin_column'] ) || isset( $rel->to['admin_column'] ) ) {
				$related_type = '';
				$related      = [];
				$label        = '';
				if ( isset( $rel->from['field']['post_type'] ) && $post_type === $rel->from['field']['post_type'] && $rel->from['admin_column'] ) {
					$this->related_type( $rel->to['field'], $related_type, $related, $label );
					$rel_type = 'to';
				}
				if ( isset( $rel->to['field']['post_type'] ) && $post_type === $rel->to['field']['post_type'] && $rel->to['admin_column'] ) {
					$this->related_type( $rel->from['field'], $related_type, $related, $label );
					$rel_type = 'from';
				}

				if ( count( $related ) > 1 ) {
						$display_html = '<input type="hidden" name="relationships['.$rel->id.'][from_to]" value="'.$rel_type.'" />';
						$display_html .= '<select class="mb_related_filter" name="relationships['.$rel->id.'][post_id]" data-post-type="'.$related_type.'">';
						
						foreach ( $related as $relate ){
							$display_html .= '<option value="'.$relate->ID.'" '.selected( $relate->post_title, $relate->ID ).'>'.$relate->post_title.'</option>';	
						}						

						$display_html .= '</select>';				
				}
			}
		}
	}

	public function related_type( $field, &$related_type, &$related, &$label ) {
		switch ( $field['type'] ) {
			case 'taxonomy_advanced':
				$related_type = $field['taxonomy'];
				$related      = get_terms( [
					'taxonomy'   => $related_type,
					'hide_empty' => false,
				] );
				$related      = array_map( function( $relate ) {
					$relate->post_title = $relate->name;
					$relate->ID         = $relate->term_id;
					unset( $relate->name );
					unset( $relate->term_id );
					return $relate;
				}, $related );
				$label        = $field['taxonomy'];
				break;
			case 'user':
				$related_type = $field['type'];
				$related      = get_users( [
					'fields' => [ 'id', 'user_nicename' ],
				] );
				$related      = array_map( function( $relate ) {
					$relate->post_title = $relate->user_nicename;
					unset( $relate->user_nicename );
					return $relate;
				}, $related );
				$label        = 'users';
				break;
			default:
				$related_type = $field['post_type'];
				$related      = get_posts( [
					'post_type'   => $related_type,
					'numberposts' => -1,
				] );
				$label        = get_post_type_object( $related_type )->label;
				break;
		}
	}

	/**
	 * Add a filter in the rooms query on the admin panel to
	 * filter by related posts
	 *
	 * @param $query WP_Query
	 */
	public function filter( $query ) {
		// Don't run this unless it's necessary
		if ( ! is_admin() ) {
			return;
		}
		global $pagenow;
		if ( 'edit.php' !== $pagenow ) {
			return;
		}
		if ( ! isset( $_GET['relationships'] ) || ! is_array( $_GET['relationships'] ) ) {
			return;
		}
		global $post_type;

		// We cannot access MB Relationship classes at this stage so we need to
		// rely 100% on data passed through the form
		foreach ( $_GET['relationships'] as $relationship => $data ) {

			$filtered_ids  = [];
			$should_filter = false;
			// print_r($query->query);
			if ( isset( $query->query['post_type'] ) && $post_type === $query->query['post_type'] && ! empty( $data['post_id'] ) && (int) $data['post_id'] !== 0 ) {
				$args    = array(
					'relationship' => array(
						'id'             => $relationship,
						$data['from_to'] => $data['post_id'], // You can pass object ID or full object
					),
					'nopaging'     => true,
					'fields'       => 'ids',
				);
				$results = new WP_Query( $args );
				// Support for multiple filters to be enabled and set
				$filtered_ids  = array_unique( array_merge( $results->posts, $filtered_ids ) );
				$should_filter = true;
			}
			if ( $should_filter ) {
				// If an empty array is passed, all posts are returned. Instead
				// we want to show no posts if no objects are related to the selected post
				if ( count( $filtered_ids ) === 0 ) {
					$filtered_ids = [ 'invalid_id' ];
				}
				$query->set( 'post__in', $filtered_ids );
			}
		}
	}

	/**
	 * Enqueue a script in the WordPress admin on edit.php.
	 * @param int $hook Hook suffix for the current admin page.
	 */
	public function enqueue_admin_script( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}
		
		// RWMB_Select_Advanced_Field::admin_enqueue_scripts();
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], '4.1.0' );
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0' );
		wp_enqueue_script( 'mbr-admin-filter', MBR_URL . '/js/admin-filter.js', [ 'select2' ], RWMB_VER, true );
	}

	/**
	 * The ajax callback to search for related posts in the select2 fields
	 */
	public function ajax_callback() {
		// we will pass post IDs and titles to this array
		$return = array();

		// you can use WP_Query, query_posts() or get_posts() here - it doesn't matter
		$search_results = new WP_Query( [
			's'              => $_GET['q'], // the search query
			// 'post_status' => 'publish', // uncomment this if you don't want drafts to be returned
			// 'ignore_sticky_posts' => 1,
			'posts_per_page' => 50, // how much to show at once
			'post_type'      => $_GET['post_type'],

		] );
		if ( $search_results->have_posts() ) :
			while ( $search_results->have_posts() ) :
				$search_results->the_post();
				// shorten the title a little
				$title    = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
				$return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
			endwhile;
		endif;
		echo json_encode( $return );
		die;
	}
}