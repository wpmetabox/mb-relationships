<?php
/**
 * The simple object factory.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Export class.
 */
class MBR_Export {
	public function __construct() {
		add_filter( 'post_row_actions', [ $this, 'add_export_link' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'export' ] );
	}

	public function add_export_link( $actions, $post ) {
		if ( 'mb-relationship' === $post->post_type ) {
			$url               = wp_nonce_url( add_query_arg( [
				'action' => 'mbr-export',
				'post[]' => $post->ID,
			] ), 'bulk-posts' ); // @see WP_List_Table::display_tablenav()
			$actions['export'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Export', 'mb-relationships' ) . '</a>';
		}

		return $actions;
	}

	public function export() {
		global $wp_query;
		$action  = isset( $_REQUEST['action'] ) && 'mbr-export' === $_REQUEST['action'];
		$action2 = isset( $_REQUEST['action2'] ) && 'mbr-export' === $_REQUEST['action2'];

		if ( ( ! $action && ! $action2 ) || empty( $_REQUEST['post'] ) ) {
			return;
		}

		check_ajax_referer( 'bulk-posts' );

		$post_ids = wp_parse_id_list( wp_unslash( $_REQUEST['post'] ) );

		$posts = $wp_query->query( [
			'post_type'              => 'mb-relationship',
			'post__in'               => $post_ids,
			'posts_per_page'         => count( $post_ids ),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		] );

		$data = [];
		foreach ( $posts as $post ) {
			$data[] = [
				'post_type'        => 'mb-relationship',
				'post_title'       => $post->post_title,
				'post_date'        => $post->post_date,
				'post_status'      => $post->post_status,
				'metas'            => [
					'settings'     => get_post_meta( $post->ID, 'settings', true ),
					'relationship' => get_post_meta( $post->ID, 'relationship', true ),
				],
			];
		}

		$file_name = 'relationships-exported';
		if ( count( $post_ids ) === 1 ) {
			$data      = reset( $data );
			$post      = $posts[0];
			$file_name = $post->post_name ?: sanitize_key( $post->post_title );
		}

		$data = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename=$file_name.json" );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $data ) );
		echo $data;
		die;
	}
}
