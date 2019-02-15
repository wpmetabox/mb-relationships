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
		$clauses = $this->handle_query_join( $clauses, $id_column, $pass_thru_order, $this->args );
		$where   = $this->handle_query_where( $clauses, $this->args );

		if ( ! isset( $this->args['relation'] ) && ! empty( $this->args['sibling'] ) ) {
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
		}
		$clauses['where'] .= empty( $clauses['where'] ) ? $where : " AND $where";

		return $clauses;
	}

	public function handle_query_join( &$clauses, $id_column, $pass_thru_order, $args ) {
		global $wpdb;
		$join_type = '';
		$criteria  = '';
		$query     = array();

		if ( isset( $args['relation'] ) ) {
			$join_type = $args['relation'];
			unset( $args['relation'] );
			$query     = $args;
		} else {
			$query[] = $args;
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
				$orderby            = "mbr.order_$direction";
				$clauses['orderby'] = 't.term_id' === $id_column ? "ORDER BY $orderby" : $orderby;
			}

			$fields             = "mbr.$direction AS mb_origin";
			$clauses['fields'] .= empty( $clauses['fields'] ) ? $fields : " , $fields";

			$criteria .= sprintf(
				" (mbr.$target = $id_column AND mbr.type = %s AND mbr.$source = %s) ",
				$wpdb->prepare( '%s', $value['id'] ),
				$items[0]
			);
		}

		$clauses['join'] .= " INNER JOIN $wpdb->mb_relationships AS mbr ON $criteria";
		return $clauses;
	}

	public function handle_query_where( &$clauses, $args ) {
		global $wpdb;
		$where_direction  = '';
		$query = $id_relationship = array();

		if ( isset( $args['relation'] ) ) {
			unset( $args['relation'] );
			$query = $args;
		} else {
			$query[] = $args;
		}

		foreach ( $query as $key => $value ) {
			$direction         = $value['direction'];
			$items             = array_map( 'absint', $value['items'] );
			$id_relationship[] =  "@@" . $value['id'] . "@@";

			$where_direction .= sprintf(
				" AND mbr.$direction IN (%s)",
				is_array( $items ) ? implode( ',', $items ) : $items
			);
		}
	 	$where = sprintf(
			"mbr.type IN (%s)",
			is_array( $id_relationship ) ? str_replace( "@@", "'", implode( ',', $id_relationship ) ) : str_replace( "@@", "'", $id_relationship )
		);
		$where .= $where_direction;
		return $where;
	}
}
