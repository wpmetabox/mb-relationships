<?php
/**
 * The relationship query class that alters the WordPress query to get the connected items.
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

/**
 * The relationship query class.
 */
class MBR_Query {
	/**
	 * The relationship query variables.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Constructor.
	 *
	 * @param array $args Relationship query variables.
	 */
	public function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * Modify the WordPress query to get connected object.
	 *
	 * @param array  $clauses         Query clauses.
	 * @param string $id_column       Database column for object ID.
	 * @param bool   $pass_thru_order If TRUE use the WP_Query orderby clause.
	 *
	 * @return mixed
	 */
	public function alter_clauses( &$clauses, $id_column, $pass_thru_order = false ) {
		$this->handle_query_join( $clauses, $id_column, $pass_thru_order );

		if ( empty( $this->args['relation'] ) && ! empty( $this->args['sibling'] ) ) {
			$this->handle_query_sibling( $clauses, $id_column );
		}

		global $wpdb;
		$clauses['groupby'] = empty( $clauses['groupby'] ) ? "$wpdb->posts.ID" : "{$clauses['groupby']}, $wpdb->posts.ID";

		return $clauses;
	}

	/**
	 * Modify query JOIN statement. Support querying by multiple relationships.
	 *
	 * @param array  $clauses         Query clauses.
	 * @param string $id_column       Database column for object ID.
	 * @param bool   $pass_thru_order If TRUE use the WP_Query orderby clause.
	 */
	public function handle_query_join( &$clauses, $id_column, $pass_thru_order ) {
		global $wpdb;

		$join_type     = 'AND';
		$relationships = [];

		if ( isset( $this->args['relation'] ) ) {
			$join_type = $this->args['relation'];
			unset( $this->args['relation'] );
			$relationships = $this->args;
		} else {
			$relationships[] = $this->args;
		}

		$criteria = [];
		foreach ( $relationships as $relationship ) {
			$source = $relationship['direction'];
			$target = 'from' === $source ? 'to' : 'from';
			$items  = array_map( 'absint', $relationship['items'] );

			if ( ! $pass_thru_order ) {
				$orderby            = "mbr.order_$source";
				$clauses['orderby'] = 't.term_id' === $id_column ? "ORDER BY $orderby" : $orderby;
			}

			$fields             = "mbr.$source AS mb_origin";
			$clauses['fields'] .= empty( $clauses['fields'] ) ? $fields : " , $fields";

			$criteria[] = sprintf(
				" (mbr.$target = $id_column AND mbr.type = %s AND mbr.$source IN (%s)) ",
				$wpdb->prepare( '%s', $relationship['id'] ),
				implode( ',', $items )
			);
		}
		$criteria = implode( " $join_type ", $criteria );

		$clauses['join'] .= " INNER JOIN $wpdb->mb_relationships AS mbr ON $criteria";
	}

	/**
	 * Modify query to get sibling items. Do not support querying by multiple relationships.
	 *
	 * @param array  $clauses   Query clauses.
	 * @param string $id_column Database column for object ID.
	 */
	public function handle_query_sibling( &$clauses, $id_column ) {
		global $wpdb;

		$source = $this->args['direction'];
		$target = 'from' === $source ? 'to' : 'from';
		$items  = array_map( 'absint', $this->args['items'] );
		$ids    = implode( ',', $items );
		$items  = "(
			SELECT DISTINCT `$target`
			FROM $wpdb->mb_relationships
			WHERE `type` = {$wpdb->prepare( '%s', $this->args['id'] )}
			AND `$source` IN ($ids)
		)";
		$tmp    = $source;
		$source = $target;
		$target = $tmp;

		$clauses['join'] = " INNER JOIN $wpdb->mb_relationships AS mbr ON mbr.$target = $id_column";

		$where  = sprintf(
			"mbr.type = %s AND mbr.$source IN (%s)",
			$wpdb->prepare( '%s', $this->args['id'] ),
			$items
		);
		$where .= " AND mbr.$target NOT IN ($ids)";

		$clauses['where'] .= empty( $clauses['where'] ) ? $where : " AND $where";
	}
}
