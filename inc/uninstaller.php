<?php
defined( 'ABSPATH' ) || die;

class MBR_Uninstaller {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_mbr_uninstall', [ $this, 'ajax_uninstall' ] );
	}

	public function enqueue_scripts() {
		if ( ! $this->is_plugins_screen() ) {
			return;
		}

		add_action( 'admin_footer', [ $this, 'print_prompt' ] );
		wp_enqueue_style( 'mbr-uninstall', MBR_URL . 'assets/uninstall.css', [], filemtime( MBR_DIR . 'assets/uninstall.css' ) );
		wp_enqueue_script( 'mbr-uninstall', MBR_URL . 'assets/uninstall.js', [], filemtime( MBR_DIR . 'assets/uninstall.js' ), true );
	}

	/**
	 * @since 2.3.0
	 * @deprecated 3.1.0
	 */
	public function localize_feedback_dialog_settings() {
		Plugin::$instance->modules_manager->get_modules( 'dev-tools' )->deprecation->deprecated_function( __METHOD__, '3.1.0' );

		return [];
	}

	public function print_prompt(): void {
		?>
		<div class="mb-modal">
			<div class="mb-modal__inner">
				<header class="mb-modal__header">
					<?php echo esc_html__( 'Delete plugin data?', 'mb-relationships' ); ?>
				</header>
				<form class="mb-modal__body" action="" method="post">
					<?php wp_nonce_field( 'delete-table' ); ?>
					<input type="hidden" name="action" value="mbr_uninstall">

					<div class="mb-modal__caption"><?php echo esc_html__( 'Do you want to delete all the plugin data when uninstalling? This includes the relationships table and the plugin option in the database.', 'mbr' ); ?></div>
					<div class="mb-modal__warning"><?php echo esc_html__( 'Please note that this action is not reversible!', 'mbr' ); ?></div>
					<fieldset class="mb-modal__choices">
						<label class="mb-modal__choice">
							<input type="radio" name="delete_table" value="yes">
							<?php esc_html_e( 'Yes, I want to delete all the plugin data to cleanup my database.' ); ?>
						</label>
						<label class="mb-modal__choice">
							<input type="radio" name="delete_table" value="no">
							<?php esc_html_e( 'No, I want to keep the plugin data so that when I reinstall the plugin, I still have my data.' ); ?>
						</label>
					</fieldset>
				</form>
				<footer class="mb-modal__footer">
					<?php submit_button( __( 'Submit & Uninstall', 'mb-relationships' ) ) ?>
				</footer>
			</div>
		</div>
		<?php
	}

	public function ajax_uninstall() {
		$wpnonce = Utils::get_super_global_value( $_POST, '_wpnonce' ); // phpcs:ignore -- Nonce verification is made in `wp_verify_nonce()`.
		if ( ! wp_verify_nonce( $wpnonce, '_mbr_uninstall_nonce' ) ) {
			wp_send_json_error();
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$reason_key  = Utils::get_super_global_value( $_POST, 'reason_key' ) ?? '';
		$reason_text = Utils::get_super_global_value( $_POST, "reason_{$reason_key}" ) ?? '';

		Api::send_feedback( $reason_key, $reason_text );

		wp_send_json_success();
	}

	private function is_plugins_screen(): bool {
		return get_current_screen()->id === 'plugins';
	}
}
