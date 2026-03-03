<?php
/**
 * Exclude already-connected posts from the relationship field dropdown.
 *
 * For relationships with 'exclude_connected' => true, this class hooks into
 * pre_get_posts and removes posts that are already connected to other posts
 * via the mb_relationships table, preventing reassignment in 1:1 relationships.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The exclude connected class.
 */
class MBR_Exclude_Connected {

	/**
	 * Map of field IDs to relationship settings.
	 *
	 * @var array<string, array>
	 */
	private $field_map = [];

	/**
	 * Setup hooks.
	 */
	public function init() {
		add_action( 'mb_relationships_registered', [ $this, 'register_field' ] );
		add_action( 'pre_get_posts', [ $this, 'exclude_connected_posts' ] );
	}

	/**
	 * When a relationship is registered with 'exclude_connected' => true,
	 * store the mapping from field ID to relationship type.
	 *
	 * @param array $settings Relationship settings.
	 */
	public function register_field( $settings ) {
		if ( empty( $settings['exclude_connected'] ) ) {
			return;
		}

		// For reciprocal relationships, only the "to" side field is registered.
		// Field ID format: "{relationship_id}_{target}" where target = "to" for reciprocal.
		if ( ! empty( $settings['reciprocal'] ) ) {
			$field_id = "{$settings['id']}_to";
			$this->field_map[ $field_id ] = [
				'type'       => $settings['id'],
				'reciprocal' => true,
			];
		} else {
			// Non-reciprocal: both sides can have exclude_connected.
			$this->field_map[ "{$settings['id']}_to" ] = [
				'type'       => $settings['id'],
				'reciprocal' => false,
			];
			$this->field_map[ "{$settings['id']}_from" ] = [
				'type'       => $settings['id'],
				'reciprocal' => false,
			];
		}
	}

	/**
	 * Exclude already-connected posts from the relationship dropdown query.
	 *
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function exclude_connected_posts( WP_Query $query ) {
		$mb_field_id = $query->get( 'mb_field_id' );
		if ( empty( $mb_field_id ) || ! isset( $this->field_map[ $mb_field_id ] ) ) {
			return;
		}

		$field_info      = $this->field_map[ $mb_field_id ];
		$relationship_id = $field_info['type'];
		$current_post_id = $this->get_current_post_id();

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT `from`, `to` FROM {$wpdb->mb_relationships} WHERE `type` = %s",
			$relationship_id
		), ARRAY_A );

		if ( empty( $rows ) ) {
			return;
		}

		// Collect all connected post IDs.
		$all_connected = [];
		foreach ( $rows as $row ) {
			$all_connected[] = (int) $row['from'];
			$all_connected[] = (int) $row['to'];
		}
		$all_connected = array_unique( $all_connected );

		// Remove the current post's connections from the exclusion list.
		// This lets the user still see posts that are already linked to THIS post.
		if ( $current_post_id > 0 ) {
			$current_connections = [];
			foreach ( $rows as $row ) {
				if ( (int) $row['from'] === $current_post_id ) {
					$current_connections[] = (int) $row['to'];
				}
				if ( (int) $row['to'] === $current_post_id ) {
					$current_connections[] = (int) $row['from'];
				}
			}
			$current_connections = array_unique( $current_connections );

			// Keep the current connections visible.
			$all_connected = array_diff( $all_connected, $current_connections );

			// Also remove the current post itself.
			$all_connected = array_diff( $all_connected, [ $current_post_id ] );
		}

		$all_connected = array_values( array_filter( $all_connected ) );

		if ( empty( $all_connected ) ) {
			return;
		}

		// Merge with existing post__not_in.
		$existing = $query->get( 'post__not_in' );
		if ( ! is_array( $existing ) ) {
			$existing = [];
		}

		$query->set( 'post__not_in', array_unique( array_merge( $existing, $all_connected ) ) );
	}

	/**
	 * Get the current post ID being edited.
	 * Works for both normal page loads and AJAX requests.
	 *
	 * @return int
	 */
	private function get_current_post_id() {
		// Normal admin edit screen.
		if ( ! wp_doing_ajax() ) {
			global $post;
			return ! empty( $post->ID ) ? (int) $post->ID : 0;
		}

		// AJAX: parse post ID from the HTTP_REFERER.
		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			$query   = wp_parse_url( $referer, PHP_URL_QUERY );
			if ( $query ) {
				parse_str( $query, $params );
				if ( ! empty( $params['post'] ) ) {
					return absint( $params['post'] );
				}
			}

			// New post — no ID yet.
			if ( strpos( $referer, 'post-new.php' ) !== false ) {
				return 0;
			}
		}

		return 0;
	}
}
