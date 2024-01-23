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
		add_action( 'pre_get_posts', [ $this, 'admin_filter' ] );
		add_action( 'wp_ajax_mbr_admin_filter', [ $this, 'get_data_options' ] );

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

		foreach ( $relationships as $relationship ) {

            if( ( ! isset( $relationship->from['admin_column'] ) && ! isset( $relationship->to['admin_column'] ) ) ||
                ! isset( $relationship->from['object_type'] ) ||
                ( isset( $relationship->to['field']['post_type'] ) && isset( $relationship->from['field']['post_type'] ) && $post_type !== $relationship->to['field']['post_type'] && $post_type !== $relationship->from['field']['post_type'] ) ) {
                continue;
            }

            if ( ( isset( $relationship->to['field']['post_type'] ) && $post_type !== $relationship->to['field']['post_type'] ) || ( isset( $relationship->from['field']['post_type'] ) && $post_type !== $relationship->from['field']['post_type'] ) ) {
				continue;
			}

            if ( in_array( $post_type, [ 'mb-relationship', 'meta-box', 'mb-settings-page', 'mb-post-type', 'mb-taxonomy' ] ) ) {
				continue;
			}
            
            $data_relation = $relationship->from['object_type'] === 'post' && $relationship->from['field']['post_type'] === $post_type ? 
                [ 'data' => $relationship->to, 'relation' => 'to' ] : 
                [ 'data' => $relationship->from, 'relation' => 'from' ];
                
            $placeholder = $data_relation['data']['object_type'] === 'term' ? 
                $data_relation['data']['field']['taxonomy'] : 
                ( $data_relation['data']['object_type'] === 'user' ? 'Users' : get_post_type_object( $data_relation['data']['field']['type'] )->label );


            $display_html  = '<input type="hidden" name="relationships[' . $relationship->id . '][from_to]" value="' . $data_relation['relation'] . '" />';
            $display_html .= '<select class="mb_related_filter" name="relationships[' . $relationship->id . '][ID]" data-mbr-filter=\'' . json_encode( $data_relation ) . '\'>';
            $display_html .= '<option value="">All ' . $placeholder . '</option>';
            $display_html .= '</select>';

            echo $display_html;
		}
	}

	/**
	 * Add a filter in the rooms query on the admin panel to
	 * filter by related posts
	 *
	 * @param $query WP_Query
	 */
	public function admin_filter( $query ) {
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

        $ids = [];

		// We cannot access MB Relationship classes at this stage so we need to
		// rely 100% on data passed through the form
		foreach ( $_GET['relationships'] as $relationship => $data ) {

			// $filtered_ids  = [];
			// $should_filter = false;
			// // print_r($query->query);
			// if ( isset( $query->query['post_type'] ) && $post_type === $query->query['post_type'] && ! empty( $data['post_id'] ) && (int) $data['post_id'] !== 0 ) {
			// 	$args    = array(
			// 		'relationship' => array(
			// 			'id'             => $relationship,
			// 			$data['from_to'] => $data['post_id'], // You can pass object ID or full object
			// 		),
			// 		'nopaging'     => true,
			// 		'fields'       => 'ids',
			// 	);
			// 	$results = new WP_Query( $args );
			// 	// Support for multiple filters to be enabled and set
			// 	$filtered_ids  = array_unique( array_merge( $results->posts, $filtered_ids ) );
			// 	$should_filter = true;
			// }
			// if ( $should_filter ) {
			// 	// If an empty array is passed, all posts are returned. Instead
			// 	// we want to show no posts if no objects are related to the selected post
			// 	if ( count( $filtered_ids ) === 0 ) {
			// 		$filtered_ids = [ 'invalid_id' ];
			// 	}
			// 	$query->set( 'post__in', $filtered_ids );
			// }
		}

        $query->set( 'post__in', $ids );
	}

	/**
	 * Enqueue a script in the WordPress admin on edit.php.
	 * @param int $hook Hook suffix for the current admin page.
	 */
	public function enqueue_admin_script( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'rwmb-select2', RWMB_CSS_URL . 'select2/select2.css', [], '4.0.10' );
		wp_register_script( 'rwmb-select2', RWMB_JS_URL . 'select2/select2.min.js', [ 'jquery' ], '4.0.10', true );
		wp_enqueue_script( 'mbr-admin-filter', MBR_URL . 'js/admin-filter.js', [ 'rwmb-select2' ], RWMB_VER, true );
	}

	/**
	 * The ajax callback to search for related posts in the select2 fields
	 */
	public function get_data_options() {

        if( ! $_GET['q'] || !$_GET['filter'] ) {
            echo json_encode([]);
            die();
        }        

        // Get Data Ajax
        $filter = $_GET['filter'];
        $options = [];

        // Data Term
        if( $filter['object_type'] === 'term' ) {
            echo json_encode( $this->get_term_options( $_GET['q'], $filter['field'] ) );
		    die;
        }        

        // Data Term
        if( $filter['object_type'] === 'user' ) {
            echo json_encode( $this->get_user_options( $_GET['q'], $filter['field'] ) );
		    die;
        }  
        
        // Data Post
        echo json_encode( $this->get_post_options( $_GET['q'], $filter['field'] ) );
        die;        
	}

    private function get_term_options( $q = '', $field = [] ) {
        $options = [];

        $terms      = get_terms( [
            'taxonomy'   => $field['taxonomy'],
            'hide_empty' => false,
            'name__like' => $q
        ] );

        if( count( $terms ) > 0 ) {
            foreach( $terms as $term ){
                $options[] = [
                    'value' => $term->term_id,
                    'label' => ( mb_strlen( $term->name ) > 50 ) ? mb_substr( $term->name, 0, 49 ) . '...' : $term->name
                ];
            }
        }

        return $options;
    }

    private function get_user_options( $q = '', $field = [] ) {
        $options = [];

        $users      = get_users( [ 
            'fields' => [ 'id', 'user_nicename', 'first_name', 'last_name' ],
            'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key'     => 'first_name',
                        'value'   => $q,
                        'compare' => 'LIKE',
                    ],
                    [
                        'key'     => 'last_name',
                        'value'   => $q,
                        'compare' => 'LIKE',
                    ],
                ],           
            ] );
        
        if( count( $users ) > 0 ) {
            foreach( $users as $user ) {
                $options[] = [
                    'value' => $user->ID,
                    'label' => $user->user_nicename
                ];
            }
        }   

        return $options;
    }    

    private function get_post_options( $q = '', $field = [] ) {
        $options = [];

        $posts      = get_posts( [
            'post_type'   => $field['post_type'],
            'numberposts' => 50,
            's' => $q
        ] );

        if( count( $posts ) > 0 ) {
            foreach( $posts as $post ) {
                $options[] = [
                    'value' => $post->ID,
                    'label' => ( mb_strlen( $post->post_title ) > 50 ) ? mb_substr( $post->post_title, 0, 49 ) . '...' : $post->post_title
                ];
            }
        }

        return $options;
    }      
}