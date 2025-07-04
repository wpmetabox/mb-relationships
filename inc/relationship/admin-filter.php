<?php
defined( 'ABSPATH' ) || die;

use MetaBox\Support\Arr;

class MBR_Admin_Filter {

	const LIMIT              = 20;
	const LIMIT_LABEL_OPTION = 50;
	private $post_type = '';

	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'load-edit.php', [ $this, 'execute' ] );

		// Get options for autocomplete for select2 filter.
		add_action( 'wp_ajax_mbr_admin_filter', [ $this, 'ajax_get_options' ] );
	}

	public function execute(): void {
		$this->post_type = $this->get_post_type();
		if ( empty( $this->post_type ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Process the filter: filter posts by relationships.
		add_action( 'pre_get_posts', [ $this, 'filter_posts_by_relationships' ] );

		// Add filter for posts. Works only for posts. Terms & users don't have a similar filter.
		add_action( 'restrict_manage_posts', [ $this, 'add_filter_for_posts' ] );
	}

	private function get_post_type(): string {
		return get_current_screen()->post_type;
	}

	public function add_filter_for_posts(): void {
		$relationships = MB_Relationships_API::get_all_relationships();
		if ( ! empty( $relationships ) ) {
			wp_nonce_field( 'filter_by_relationships', 'mbr_filter_nonce' );
		}
		array_walk( $relationships, [ $this, 'add_filter_select' ] );
	}

	private function add_filter_select( MBR_Relationship $relationship ): void {
		$from = $relationship->from;
		$to   = $relationship->to;

		// Add filters for posts only.
		if ( Arr::get( $from, 'object_type' ) !== 'post' && Arr::get( $to, 'object_type' ) !== 'post' ) {
			return;
		}

		// Only show filters for current post type.
		if ( Arr::get( $from, 'field.post_type' ) !== $this->post_type && Arr::get( $to, 'field.post_type' ) !== $this->post_type ) {
			return;
		}

		// Get data from or to relationship with current post type
		$data = Arr::get( $from, 'field.post_type' ) === $this->post_type
			? [
				'object_type'  => $to['object_type'],
				'type'         => $this->get_type( $to ),
				'relation'     => 'to',
				'label'        => $from['meta_box']['title'],
				'admin_filter' => Arr::get( $from, 'admin_filter', false ),
			]
			: [
				'object_type'  => $from['object_type'],
				'type'         => $this->get_type( $from ),
				'relation'     => 'from',
				'label'        => $to['meta_box']['title'],
				'admin_filter' => Arr::get( $to, 'admin_filter', false ),
			];

		if ( ! $data['admin_filter'] ) {
			return;
		}

		$selected = $this->get_selected_item( $relationship->id, $data['object_type'] );
		printf(
			'<input type="hidden" name="relationships[%s][from_to]" value="%s" />
			<select class="mb_related_filter" name="relationships[%s][ID]" data-object_type="%s" data-type="%s" data-placeholder="%s">
				<option value="">%s</option>
				%s
			</select>',
			esc_attr( $relationship->id ),
			esc_attr( $data['relation'] ),
			esc_attr( $relationship->id ),
			esc_attr( $data['object_type'] ),
			esc_attr( $data['type'] ),
			esc_attr( $data['label'] ),
			esc_html( $data['label'] ),
			$selected ? '<option value="' . esc_attr( $selected['id'] ) . '" selected>' . esc_html( $selected['text'] ) . '</option>' : ''
		);
	}

	private function get_type( array $side ): string {
		if ( $side['object_type'] === 'post' ) {
			return $side['field']['post_type'];
		}

		if ( $side['object_type'] === 'term' ) {
			return $side['field']['taxonomy'];
		}

		return '';
	}

	public function filter_posts_by_relationships( WP_Query $query ): void {
		$nonce = sanitize_text_field( wp_unslash( $_GET['mbr_filter_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'filter_by_relationships' ) ) {
			return;
		}

		if ( ! isset( $_GET['relationships'] ) || ! is_array( $_GET['relationships'] ) ) {
			return;
		}

		$ids           = [];
		$should_filter = false;

		// We cannot access MB Relationship classes at this stage so we need to rely 100% on data passed through the form
		$relationships = wp_unslash( $_GET['relationships'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( $relationships as $relationship => $data ) {
			// Sanitize inputs.
			$relationship = sanitize_text_field( $relationship );
			$direction    = isset( $data['from_to'] ) && in_array( $data['from_to'], [ 'from', 'to' ], true ) ? $data['from_to'] : 'from';
			$id           = (int) ( $data['ID'] ?? 0 );

			if ( ! $id ) {
				continue;
			}

			if ( ! isset( $query->query['post_type'] ) || $this->post_type !== $query->query['post_type'] ) {
				continue;
			}

			$results = new WP_Query( [
				'relationship' => [
					'id'       => $relationship,
					$direction => $id,
				],
				'nopaging'     => true,
				'fields'       => 'ids',
			] );

			$should_filter = true;

			if ( empty( $ids ) ) {
				$ids = array_unique( $results->posts );
				continue;
			}

			$ids = array_intersect( $ids, $results->posts );
		}

		if ( ! $should_filter ) {
			return;
		}

		$query->set( 'post__in', count( $ids ) === 0 ? [ 'invalid_id' ] : $ids );
	}

	public function enqueue_assets(): void {
		wp_enqueue_style( 'rwmb-select2', RWMB_CSS_URL . 'select2/select2.css', [], '4.0.10' );
		wp_style_add_data( 'rwmb-select2', 'path', RWMB_CSS_DIR . 'select2/select2.css' );
		wp_register_script( 'rwmb-select2', RWMB_JS_URL . 'select2/select2.min.js', [ 'jquery' ], '4.0.10', true );

		// Localize
		$locale       = str_replace( '_', '-', get_user_locale() );
		$locale_short = substr( $locale, 0, 2 );
		$locale       = file_exists( RWMB_DIR . "js/select2/i18n/$locale.js" ) ? $locale : $locale_short;

		if ( file_exists( RWMB_DIR . "js/select2/i18n/$locale.js" ) ) {
			wp_enqueue_script( 'rwmb-select2-i18n', RWMB_JS_URL . "select2/i18n/$locale.js", [ 'rwmb-select2' ], '4.0.10', true );
		}

		wp_enqueue_style( 'mbr-admin-filter', MBR_URL . 'css/admin-filter.css', [], filemtime( MBR_DIR . 'css/admin-filter.css' ) );
		wp_style_add_data( 'mbr-admin-filter', 'path', MBR_DIR . 'css/admin-filter.css' );
		wp_enqueue_script( 'mbr-admin-filter', MBR_URL . 'js/admin-filter.js', [ 'rwmb-select2', 'rwmb-select2-i18n' ], filemtime( MBR_DIR . 'js/admin-filter.js' ), true );
		wp_localize_script( 'mbr-admin-filter', 'MBR', [
			'nonce' => wp_create_nonce( 'load-options' ),
		] );
	}

	/**
	 * The ajax callback to search for related posts in the select2 fields
	 */
	public function ajax_get_options(): void {
		check_ajax_referer( 'load-options' );

		// Return ajax if keyword or data filter empty
		if ( empty( $_GET['q'] ) || empty( $_GET['object_type'] ) ) {
			wp_send_json_error( [] );
		}

		$q           = sanitize_text_field( wp_unslash( $_GET['q'] ) );
		$object_type = sanitize_text_field( wp_unslash( $_GET['object_type'] ) );
		$type        = sanitize_text_field( wp_unslash( $_GET['type'] ?? '' ) );
		$options     = $this->get_data_options( $q, $object_type, $type );
		wp_send_json_success( $options );
	}

	private function get_selected_item( string $relationship_id, string $object_type ): array {
		$nonce = sanitize_text_field( wp_unslash( $_GET['mbr_filter_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'filter_by_relationships' ) ) {
			return [];
		}

		if ( ! isset( $_GET['relationships'] ) || ! is_array( $_GET['relationships'] ) ) {
			return [];
		}

		$id = (int) Arr::get( $_GET, "relationships.{$relationship_id}.ID" );
		if ( empty( $id ) ) {
			return [];
		}

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

		return [];
	}

	private function get_data_options( string $q, string $object_type, string $type ): array {
		if ( $object_type === 'term' ) {
			return $this->get_term_options( $q, $type );
		}

		if ( $object_type === 'user' ) {
			return $this->get_user_options( $q );
		}

		// Data Post
		return $this->get_post_options( $q, $type );
	}

	private function get_term_options( string $q, string $taxonomy ): array {
		$query = new WP_Term_Query( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'name__like' => $q,
			'number'     => self::LIMIT,
		] );

		$options = [];
		foreach ( $query->terms as $term ) {
			$options[] = [
				'id'   => $term->term_id,
				'text' => $this->truncate_label_option( $term->name ),
			];
		}

		return $options;
	}

	private function get_user_options( string $q ): array {
		$query = new WP_User_Query( [
			'fields'         => [ 'id', 'display_name' ],
			'search'         => '*' . esc_attr( $q ) . '*',
			'search_columns' => [ 'display_name' ],
			'number'         => self::LIMIT,
		] );

		$options = [];
		foreach ( $query->get_results() as $user ) {
			$options[] = [
				'id'   => $user->ID,
				'text' => $this->truncate_label_option( $user->display_name ),
			];
		}

		return $options;
	}

	private function get_post_options( string $q, string $post_type ): array {
		$posts = new WP_Query( [
			'post_type'   => $post_type,
			'numberposts' => self::LIMIT,
			's'           => $q,
		] );

		$options = [];
		foreach ( $posts->posts as $post ) {
			$options[] = [
				'id'   => $post->ID,
				'text' => $this->truncate_label_option( $post->post_title ),
			];
		}

		return $options;
	}

	private function truncate_label_option( string $label = '' ): string {
		return mb_strlen( $label ) > self::LIMIT_LABEL_OPTION ? mb_substr( $label, 0, self::LIMIT_LABEL_OPTION ) . '...' : $label;
	}
}
