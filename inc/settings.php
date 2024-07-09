<?php
class MBR_Settings {
	public function __construct() {
		add_filter( 'mb_settings_pages', [ $this, 'register_settings_page' ] );
		add_filter( 'rwmb_meta_boxes', [ $this, 'register_settings_fields' ] );
	}

	public function register_settings_page( $settings_pages ) {
		$settings_pages[] = [
			'menu_title' => __( 'Settings Realationships', 'mb-relationships' ),
			'id'         => 'settings-relationships',
			'position'   => 1,
			'parent'     => 'meta-box',
			'capability' => 'manage_options',
			'style'      => 'no-boxes',
			'columns'    => 1,
		];

		return $settings_pages;
	}

	public function register_settings_fields( $meta_boxes ) {
		$meta_boxes[] = [
			'title'          => __( 'Delete data', 'mb-relationships' ),
			'id'             => 'mbr-setting-delete-data',
			'settings_pages' => [ 'settings-relationships' ],
			'fields'         => [
				[
					'name' => __( 'Delete data in database?', 'mb-relationships' ),
					'id'   => 'delete_data',
					'type' => 'switch',
					'desc' => __( 'Delete data in database if the relationship is deleted in builder.', 'mb-relationships' ),
				],
			],
		];
		return $meta_boxes;
	}
}
