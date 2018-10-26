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
	 * @param array  $clauses   Query clauses.
	 * @param string $id_column Database column for object ID.
	 *
	 * @return mixed
	 */
	public function alter_clauses( &$clauses, $id_column, $pass_thru_order = FALSE ) {
		global $wpdb;
		$direction = $this->args['direction'];
		$connected = 'from' === $direction ? 'to' : 'from';
		$items     = array_map( 'absint', $this->args['items'] );

		$fields             = "mbr.$direction AS mb_origin";
		$clauses['fields'] .= empty( $clauses['fields'] ) ? $fields : " , $fields";

		if ( ! empty( $this->args['sibling'] ) ) {
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
		}

		$clauses['join']   .= " INNER JOIN $wpdb->mb_relationships AS mbr ON mbr.$connected = $id_column";

		if ( ! $pass_thru_order ) {
			$clauses['orderby'] = 't.term_id' === $id_column ? 'ORDER BY mbr.ID' : 'mbr.ID';
		}

		$where = sprintf(
			"mbr.type = %s AND mbr.$direction IN (%s)",
			$wpdb->prepare( '%s', $this->args['id'] ),
			is_array( $items ) ? implode( ',', $items ) : $items
		);

		if ( ! empty( $this->args['sibling'] ) ) {
			$where .= " AND mbr.$connected NOT IN ($ids)";
		}

		$clauses['where'] .= empty( $clauses['where'] ) ? $where : " AND $where";

		return $clauses;
	}
}
