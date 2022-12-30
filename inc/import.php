<?php
/**
 * The simple object factory.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Import class.
 */
class MBR_Import {
	public function __construct() {
		add_action( 'admin_footer-edit.php', [ $this, 'output_js_templates' ] );
		add_action( 'admin_print_styles-edit.php', [ $this, 'enqueue' ] );
		add_action( 'admin_init', [ $this, 'import' ] );
	}

	public function enqueue() {
		if ( 'edit-mb-relationship' !== get_current_screen()->id ) {
			return;
		}

		wp_enqueue_style( 'mb-relationships',  plugin_dir_url( __DIR__ ) . 'assets/import.css', [], RWMB_VER );
		wp_enqueue_script( 'mb-relationships', plugin_dir_url( __DIR__ ) . 'assets/import.js', [ 'jquery' ], RWMB_VER, true );

		wp_localize_script( 'mb-relationships', 'MBR', [
			'export' => esc_html__( 'Export', 'mb-relationships' ),
			'import' => esc_html__( 'Import', 'mb-relationships' ),
		] );
	}

	public function output_js_templates() {
		if ( 'edit-mb-relationship' !== get_current_screen()->id ) {
			return;
		}

		?>
		<?php if ( isset( $_GET['imported'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Relationships have been imported successfully!', 'mb-relationships' ); ?></p></div>
		<?php endif; ?>

		<script type="text/template" id="mbr-import-form">
			<div class="mbr-import-form">
				<p><?php esc_html_e( 'Choose an exported ".json" file from your computer:', 'mb-relationships' ); ?></p>
				<form enctype="multipart/form-data" method="post" action="">
					<?php wp_nonce_field( 'import' ); ?>
					<input type="file" name="mbr_file">
					<?php submit_button( esc_attr__( 'Import', 'mb-relationships' ), 'secondary', 'submit', false, [ 'disabled' => true ] ); ?>
				</form>
			</div>
		</script>
		<?php
	}

	public function import() {
		if ( empty( $_FILES['mbr_file'] ) || empty( $_FILES['mbr_file']['tmp_name'] ) ) {
			return;
		}

		check_ajax_referer( 'import' );

		$url    = admin_url( 'edit.php?post_type=mb-relationship' );
		$data   = file_get_contents( sanitize_text_field( wp_unslash( $_FILES['mbr_file']['tmp_name'] ) ) );
		$result = $this->import_json( $data );

		if ( ! $result ) {
			// Translators: %s - go back URL.
			wp_die( wp_kses_post( sprintf( __( 'Invalid file data. <a href="%s">Go back</a>.', 'mb-relationships' ), $url ) ) );
		}

		$url = add_query_arg( 'imported', 'true', $url );
		wp_safe_redirect( $url );
		die;
	}

	private function import_json( $data ) {
		$posts = json_decode( $data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		// If import only one post.
		if ( isset( $posts['post_title'] ) ) {
			$posts = [ $posts ];
		}

		foreach ( $posts as $post ) {
			$post_id = wp_insert_post( $post );
			if ( ! $post_id ) {
				wp_die( wp_kses_post( sprintf(
					// Translators: %s - go back URL.
					__( 'Cannot import the relationships <strong>%1$s</strong>. <a href="%2$s">Go back</a>.', 'mb-relationships' ),
					$post['title'],
					admin_url( 'edit.php?post_type=mb-relationship' )
				) ) );
			}
			if ( is_wp_error( $post_id ) ) {
				wp_die( wp_kses_post( implode( '<br>', $post_id->get_error_messages() ) ) );
			}

			foreach ( $post['metas'] as $meta => $value ) {
				$this->update_postmeta( $post_id, $meta, $value );
			}
		}

		return true;
	}
	private function update_postmeta( $post_id, $meta, $value ) {
		update_post_meta( $post_id, $meta, $value );
	}
}
