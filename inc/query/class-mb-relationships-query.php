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
		$items     = $this->args['items'];

		$join  = " INNER JOIN $wpdb->mb_relationships ON $wpdb->mb_relationships.$connected = $id_column";
		$where = $wpdb->prepare( "$wpdb->mb_relationships.type = %s", $this->args['id'] );
		$where .= $wpdb->prepare( " AND $wpdb->mb_relationships.$direction IN (%s)", implode( ',', $items ) );

		$clauses['join']  .= $join;
		$clauses['where'] .= " AND $where";

		return $clauses;
	}
}
