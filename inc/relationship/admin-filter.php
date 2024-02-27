<?php
class MBR_Admin_Filter {

	const LIMIT = 20;

	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		// Add filter for posts. Works only for posts. Terms & users don't have a similar filter.
		add_action( 'restrict_manage_posts', [ $this, 'add_filter_for_posts' ] );

		// Get options for autocomplete for select2 filter.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_script' ] );
		add_action( 'wp_ajax_mbr_admin_filter', [ $this, 'ajax_get_options' ] );

		// Process the filter: filter posts by relationships.
		add_action( 'pre_get_posts', [ $this, 'filter_posts_by_relationships' ] );
	}

	public function add_filter_for_posts() {
		$relationships = MB_Relationships_API::get_all_relationships();
		array_walk( $relationships, [ $this, 'add_filter_select' ] );
	}

	private function add_filter_select( $relationship ) {

		// Check object_type not post
		if ( ( ! $relationship->from['object_type'] || $relationship->from['object_type'] !== 'post' ) && ( ! $relationship->to['object_type'] || $relationship->to['object_type'] !== 'post' ) ) {
			return;
		}

		global $post_type;
		// Only show filter on with curren post type
		if ( ( ! isset( $relationship->from['field']['post_type'] ) || $post_type !== $relationship->from['field']['post_type'] ) && ( ! isset( $relationship->to['field']['post_type'] ) || $post_type !== $relationship->to['field']['post_type'] ) ) {
			return;
		}

		// Get data from or to relationship with current post type
		$data_relation = isset( $relationship->from['field']['post_type'] ) && $relationship->from['field']['post_type'] === $post_type ?
		[
			'data'     => $relationship->to,
			'relation' => 'to',
			'label'    => $relationship->to['meta_box']['title'] === $relationship->label_to ? $relationship->label_from : $relationship->to['meta_box']['title'],
		] :
		[
			'data'     => $relationship->from,
			'relation' => 'from',
			'label'    => $relationship->from['meta_box']['title'] === $relationship->label_from ? $relationship->label_to : $relationship->from['meta_box']['title'],
		];

		$selected = isset( $_GET['relationships'] ) ? $this->get_data_options( '', $data_relation['data'], $_GET['relationships'][ $relationship->id ]['ID'] ) : '';
		echo $this->get_html_select_filter( $relationship, $data_relation, $data_relation['label'], $selected );
	}

	private function get_html_select_filter( $relationship, $data, $placeholder, $selected ) {
		$html_select  = '<input type="hidden" name="relationships[' . $relationship->id . '][from_to]" value="' . $data['relation'] . '" />';
		$html_select .= '<select class="mb_related_filter" name="relationships[' . $relationship->id . '][ID]" data-mbr-filter=\'' . json_encode( $data ) . '\'>';
		$html_select .= '<option value="">' . $placeholder . '</option>';

		if ( $selected ) {
			$html_select .= '<option value="' . $selected['value'] . '" selected>' . $selected['label'] . '</option>';
		}

		$html_select .= '</select>';

		return $html_select;
	}

	public function filter_posts_by_relationships( $query ) {
		if ( ! is_admin() ) {
			return;
		}
		global $pagenow, $post_type;

		if ( 'edit.php' !== $pagenow || ! isset( $_GET['relationships'] ) || ! is_array( $_GET['relationships'] ) ) {
			return;
		}

		$ids           = [];
		$should_filter = false;

		// We cannot access MB Relationship classes at this stage so we need to
		// rely 100% on data passed through the form
		foreach ( $_GET['relationships'] as $relationship => $data ) {

			if ( empty( $data['ID'] ) ) {
				continue;
			}

			if ( isset( $query->query['post_type'] ) && $post_type === $query->query['post_type'] ) {
				$results = new WP_Query( [
					'relationship' => [
						'id'             => $relationship,
						$data['from_to'] => $data['ID'],
					],
					'nopaging'     => true,
					'fields'       => 'ids',
				] );

				$ids           = empty( $ids ) ? array_unique( array_merge( $results->posts, $ids ) ) : array_intersect( $ids, $results->posts );
				$should_filter = true;
			}
		}

		if ( $should_filter ) {
			if ( count( $ids ) === 0 ) {
				$ids = [ 'invalid_id' ];
			}
			$query->set( 'post__in', $ids );
		}
	}

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
	public function ajax_get_options() {

		// Return ajax if keyword or data filter empty
		if ( empty( $_GET['q'] ) || empty( $_GET['filter'] ) ) {
			wp_send_json_success( [] );
		}

		$options = $this->get_data_options( $_GET['q'], $_GET['filter'] );
		wp_send_json_success( $options );
	}

	private function get_data_options( $q, $data, $id = null ) {
		// Data Term
		if ( $data['object_type'] === 'term' ) {
			return $this->get_term_options( $q, $data, $id );
		}

		// Data Term
		if ( $data['object_type'] === 'user' ) {
			return $this->get_user_options( $q, $data, $id );
		}

		// Data Post
		return $this->get_post_options( $q, $data, $id );
	}

	private function get_term_options( $q = '', $field = [], $id ) {
		// If ID not empty, get one option
		if ( ! empty( $id ) ) {
			$term = get_term( $id );
			return [
				'value' => $term->term_id,
				'label' => ( mb_strlen( $term->name ) > 50 ) ? mb_substr( $term->name, 0, 49 ) . '...' : $term->name,
			];
		}

		// Get multiple options
		$options = [];

		$terms = new WP_Term_Query( [
			'taxonomy'   => $field['taxonomy'],
			'hide_empty' => false,
			'name__like' => $q,
            'number'  => self::LIMIT
		] );

		if ( count( $terms->terms ) > 0 ) {
			foreach ( $terms->terms as $term ) {
				$options[] = [
					'value' => $term->term_id,
					'label' => ( mb_strlen( $term->name ) > 50 ) ? mb_substr( $term->name, 0, 49 ) . '...' : $term->name,
				];
			}
		}

		return $options;
	}

	private function get_user_options( $q = '', $field = [], $id ) {
		// If ID not empty, get one option
		if ( ! empty( $id ) ) {
			$user = get_user_by( 'id', $id );
			return [
				'value' => $user->ID,
				'label' => $user->display_name,
			];
		}

		// Get multiple options
		$options = [];

		add_filter( 'user_search_columns', [ $this, 'search_users_by_display_name' ], 10, 3 );

		$users = new WP_User_Query( [
			'fields'         => [ 'id', 'display_name' ],
			'search'         => '*' . esc_attr( $q ) . '*',
			'search_columns' => [ 'display_name' ],
            'number'  => self::LIMIT
		] );

		remove_filter( 'user_search_columns', [ $this, 'search_users_by_display_name' ], 10 );

		if ( $users->total_users > 0 ) {
			foreach ( $users->results as $user ) {
				$options[] = [
					'value' => $user->ID,
					'label' => $user->display_name,
				];
			}
		}

		return $options;
	}

	private function get_post_options( $q = '', $field = [], $id ) {
		// If ID not empty, get one option
		if ( ! empty( $id ) ) {
			$post = get_post( $id );
			return [
				'value' => $post->ID,
				'label' => ( mb_strlen( $post->post_title ) > 50 ) ? mb_substr( $post->post_title, 0, 49 ) . '...' : $post->post_title,
			];
		}

		// Get multiple options
		$options = [];

		$posts = new WP_Query( [
			'post_type'   => $field['post_type'],
			'numberposts' => self::LIMIT,
			's'           => $q,
		] );

		if ( $posts->post_count > 0 ) {
			foreach ( $posts->posts as $post ) {
				$options[] = [
					'value' => $post->ID,
					'label' => ( mb_strlen( $post->post_title ) > 50 ) ? mb_substr( $post->post_title, 0, 49 ) . '...' : $post->post_title,
				];
			}
		}

		return $options;
	}

	public function search_users_by_display_name( $search_columns, $search, $query ) {
		$search_columns[] = 'display_name';
		return $search_columns;
	}
}
