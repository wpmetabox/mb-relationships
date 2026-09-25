<?php
/**
 * Smoke test: posts ↔ custom table models.
 *
 * Requires MB Custom Table and a registered model (e.g. mb-custom-table/tests/05-transactional-model.php).
 *
 * Usage:
 *   wp eval-file tests/10-posts-to-models.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action( 'mb_relationships_init', function () {
	MB_Relationships_API::register( [
		'id'   => 'posts_to_transactions',
		'from' => 'post',
		'to'   => [
			'object_type' => 'model',
			'model'       => 'transaction',
			'field'       => [
				'item_title' => '{transaction_id} — {amount} {currency}',
			],
		],
	] );
} );
