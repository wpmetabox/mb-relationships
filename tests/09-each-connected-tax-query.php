<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Regression test for issue #62:
// When tax_query is used in each_connected, only the first-ordered connected
// items were returned. Root cause: the relationship JOIN's GROUP BY was set to
// 'mbr_from, mbr_to' / 'mbr.$source' and overwrote the user's groupby (which WP
// derives from tax_query), causing rows to collapse. The fix preserves both.
add_action(
	'mb_relationships_init',
	function () {
		MB_Relationships_API::register(
			[
				'id'   => 'A_to_B',
				'from' => [
					'object_type'  => 'post',
					'post_type'    => 'post',
					'admin_column' => 'after title',
				],
				'to'   => [
					'object_type'  => 'post',
					'post_type'    => 'page',
					'admin_column' => 'after title',
				],
			]
		);
	}
);

add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_page() ) {
			return $content;
		}

		$all_bs = new WP_Query(
			[
				'post_type'      => 'page',
				'posts_per_page' => -1,
			]
		);

		MB_Relationships_API::each_connected(
			[
				'id'       => 'A_to_B',
				'to'       => $all_bs->posts,
				'property' => 'connected_A',
			],
			[
				'tax_query' => [
					[
						'taxonomy'         => 'category',
						'field'            => 'term_id',
						'terms'            => 10,
						'include_children' => false,
					],
				],
			],
		);

		$output = '<ul class="each-connected-tax-query">';
		foreach ( $all_bs->posts as $b ) {
			$connected = isset( $b->connected_A ) ? $b->connected_A : [];
			$output   .= '<li>' . $b->post_title . '</li>';
			if ( ! empty( $connected ) ) {
				$output .= '<ul>';
				foreach ( $connected as $a ) {
					$output .= '<li>' . $a->post_title . '</li>';
				}
				$output .= '</ul>';
			}
		}
		$output .= '</ul>';

		return $content . $output;
	}
);
