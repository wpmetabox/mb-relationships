<?php
defined( 'ABSPATH' ) || die;

use MetaBox\Support\Arr;

class MBR_Admin_Filter {

	const LIMIT              = 20;
	const LIMIT_LABEL_OPTION = 50;

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
		$from = $relationship->from;
		$to   = $relationship->to;

		// Add filters for posts only.
		if ( Arr::get( $from, 'object_type' ) !== 'post' && Arr::get( $to, 'object_type' ) !== 'post' ) {
			return;
		}

		// Only show filters for current post type.
		global $post_type;
		if ( Arr::get( $from, 'field.post_type' ) !== $post_type && Arr::get( $to, 'field.post_type' ) !== $post_type ) {
			return;
		}

		// Get data from or to relationship with current post type
		$data = Arr::get( $from, 'field.post_type' ) === $post_type
			? [
				'data'     => $to,
				'relation' => 'to',
				'label'    => $from['meta_box']['title'],
			]
			: [
				'data'     => $from,
				'relation' => 'from',
				'label'    => $to['meta_box']['title'],
			];

		$selected = isset( $_GET['relationships'] ) ? $this->get_selected_item( Arr::get( $_GET, "relationships.{$relationship->id}.ID" ), $data['data']['object_type'] ) : [];
		echo $this->get_html_select_filter( $relationship, $data, $data['label'], $selected );
	}

	private function get_html_select_filter( $relationship, $data, $placeholder, $selected ) {
		return sprintf(
			'<input type="hidden" name="relationships[%s][from_to]" value="%s" />
            <select class="mb_related_filter" name="relationships[%s][ID]" data-mbr-filter=\'%s\'>
                <option value="">%s</option>
                %s
            </select>',
			$relationship->id,
			esc_attr( $data['relation'] ),
			$relationship->id,
			esc_attr( wp_json_encode( $data['data'] ) ),
			esc_html( $placeholder ),
			$selected ? '<option value="' . esc_attr( $selected['id'] ) . '" selected>' . esc_html( $selected['text'] ) . '</option>' : ''
		);
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

	private function get_selected_item( $id, $object_type ) {
		if ( $object_type === 'term' ) {
			$term = get_term( $id );
			return [
				'id'   => $term->term_id,
				'text' => $this->truncate_label_option( $term->name ),
			];
		}

		if ( $object_type === 'user' ) {
			$user = get_user_by( 'id', $id );
			return [
				'id'   => $user->ID,
				'text' => $this->truncate_label_option( $user->display_name ),
			];
		}

		if ( $object_type === 'post' ) {
			$post = get_post( $id );
			return [
				'id'   => $post->ID,
				'text' => $this->truncate_label_option( $post->post_title ),
			];
		}
	}

	private function get_data_options( $q, $data ) : array {
		// Data Term
		if ( $data['object_type'] === 'term' ) {
			return $this->get_term_options( $q, $data['field'] );
		}

		// Data Term
		if ( $data['object_type'] === 'user' ) {
			return $this->get_user_options( $q, $data['field'] );
		}

		// Data Post
		return $this->get_post_options( $q, $data['field'] );
	}

	private function get_term_options( $q, $field ) : array {
		// Get multiple options
		$options = [];

		$terms = new WP_Term_Query( [
			'taxonomy'   => $field['taxonomy'],
			'hide_empty' => false,
			'name__like' => $q,
			'number'     => self::LIMIT,
		] );

		if ( count( $terms->terms ) === 0 ) {
			return $options;
		}

		foreach ( $terms->terms as $term ) {
			$options[] = [
				'id'   => $term->term_id,
				'text' => $this->truncate_label_option( $term->name ),
			];
		}
		return $options;
	}

	private function get_user_options( $q, $field ) : array {
		// Get multiple options
		$options = [];

		add_filter( 'user_search_columns', [ $this, 'search_users_by_display_name' ], 10, 3 );

		$users = new WP_User_Query( [
			'fields'         => [ 'id', 'display_name' ],
			'search'         => '*' . esc_attr( $q ) . '*',
			'search_columns' => [ 'display_name' ],
			'number'         => self::LIMIT,
		] );

		remove_filter( 'user_search_columns', [ $this, 'search_users_by_display_name' ], 10 );

		if ( $users->total_users === 0 ) {
			return $options;
		}

		foreach ( $users->results as $user ) {
			$options[] = [
				'id'   => $user->ID,
				'text' => $this->truncate_label_option( $user->display_name ),
			];
		}

		return $options;
	}

	private function get_post_options( $q, $field ) : array {
		// Get multiple options
		$options = [];

		$posts = new WP_Query( [
			'post_type'   => $field['post_type'],
			'numberposts' => self::LIMIT,
			's'           => $q,
		] );

		if ( $posts->post_count === 0 ) {
			return $options;
		}

		foreach ( $posts->posts as $post ) {
			$options[] = [
				'id'   => $post->ID,
				'text' => $this->truncate_label_option( $post->post_title ),
			];
		}

		return $options;
	}

	private function truncate_label_option( $label = '' ) : string {
		return mb_strlen( $label ) > self::LIMIT_LABEL_OPTION ? mb_substr( $label, 0, self::LIMIT_LABEL_OPTION ) . '...' : $label;
	}

	public function search_users_by_display_name( $search_columns, $search, $query ) : array {
		$search_columns[] = 'display_name';
		return $search_columns;
	}
}
