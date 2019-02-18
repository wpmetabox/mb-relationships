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
class MB_Relationships_Query {
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
	 * @param array  	$clauses   			Query clauses.
	 * @param string 	$id_column 			Database column for object ID.
	 * @param boolean 	$pass_thru_order 	If TRUE use the WP_Query orderby clause
	 *
	 * @return mixed
	 */
	public function alter_clauses( &$clauses, $id_column, $pass_thru_order = FALSE ) {
		global $wpdb;
		$this->handle_query_join( $clauses, $id_column, $pass_thru_order );

		if ( ! isset( $this->args['relation'] ) && ! empty( $this->args['sibling'] ) ) {
			$this->handle_query_sibling( $clauses, $id_column );
		}

		return $clauses;
	}

	public function handle_query_join( &$clauses, $id_column, $pass_thru_order ) {
		global $wpdb;
		$join_type = '';
		$criteria  = '';
		$query     = array();

		if ( isset( $this->args['relation'] ) ) {
			$join_type = $this->args['relation'];
			unset( $this->args['relation'] );
			$query     = $this->args;
		} else {
			$query[] = $this->args;
		}

		foreach ( $query as $key => $value ) {
			$direction = $value['direction'];
			$source    = $direction;
			$target    = 'from' === $direction ? 'to' : 'from';
			$items     = array_map( 'absint', $value['items'] );

			if ( strlen( $criteria ) > 0 ) {
				$criteria .= " $join_type ";
			}

			if ( ! $pass_thru_order ) {
				$orderby            = "mbr.order_$source";
				$clauses['orderby'] = 't.term_id' === $id_column ? "ORDER BY $orderby" : $orderby;
			}

			$fields             = "mbr.$source AS mb_origin";
			$clauses['fields'] .= empty( $clauses['fields'] ) ? $fields : " , $fields";

			$criteria .= sprintf(
				" (mbr.$target = $id_column AND mbr.type = %s AND mbr.$source IN (%s)) ",
				$wpdb->prepare( '%s', $value['id'] ),
				is_array( $items ) ? implode( ',', $items ) : $items
			);
		}

		$clauses['join'] .= " INNER JOIN $wpdb->mb_relationships AS mbr ON $criteria";
	}

	public function handle_query_sibling( &$clauses, $id_column ) {
		global $wpdb;
		$direction = $this->args['direction'];
		$connected = 'from' === $direction ? 'to' : 'from';
		$items     = array_map( 'absint', $this->args['items'] );
		$ids       = implode( ',', $items );
		$items     = "(
			SELECT DISTINCT `{$connected}`
			FROM {$wpdb->mb_relationships}
			WHERE `type` = {$wpdb->prepare( '%s', $this->args['id'] )}
			AND `{$direction}` IN ({$ids})
		)";
		$tmp       = $direction;
		$direction = $connected;
		$connected = $tmp;

		$clauses['join'] = " INNER JOIN $wpdb->mb_relationships AS mbr ON mbr.$connected = $id_column";

		$where = sprintf(
			"mbr.type = %s AND mbr.$direction IN (%s)",
			$wpdb->prepare( '%s', $this->args['id'] ),
			is_array( $items ) ? implode( ',', $items ) : $items
		);
		$where .= " AND mbr.$connected NOT IN ($ids)";
		$clauses['where'] .= empty( $clauses['where'] ) ? $where : " AND $where";
	}
}
