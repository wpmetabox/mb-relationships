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
	public function alter_clauses( &$clauses, $id_column ) {
		global $wpdb;

		$direction = $this->args['direction'];
		$connected = 'from' === $direction ? 'to' : 'from';
		$items     = array_map( 'absint', $this->args['items'] );

		$fields            = "mbr.$direction AS mb_origin";
		$clauses['fields'] .= empty( $clauses['fields'] ) ? $fields : " , $fields";

		$clauses['join'] .= " INNER JOIN $wpdb->mb_relationships AS mbr ON mbr.$connected = $id_column";

		$where            = sprintf(
			"mbr.type = %s AND mbr.$direction IN (%s)",
			$wpdb->prepare( '%s', $this->args['id'] ),
			implode( ',', $items )
		);
		$clauses['where'] .= empty( $clauses['where'] ) ? $where : " AND $where";

		return $clauses;
	}
}
