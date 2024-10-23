<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action(
	'mb_relationships_init',
	function () {
		MB_Relationships_API::register(
			array(
				'id'   => 'posts_to_pages',
				'from' => 'post',
				'to'   => 'page',
			)
		);
		MB_Relationships_API::register(
			array(
				'id'   => 'posts_to_posts',
				'from' => 'post',
				'to'   => 'post',
			)
		);
	}
);
