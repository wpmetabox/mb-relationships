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

	/**
	 * Given a list of objects and another list of connected items,
	 * distribute each connected item to it's respective counterpart.
	 *
	 * @param array  $items     List of objects.
	 * @param array  $connected List of connected objects.
	 * @param string $property  Name of connected array property.
	 * @param string $id_key    ID key of the objects.
	 *
	 * @return array
	 */
	public static function distribute( &$items, $connected, $property, $id_key ) {
		foreach ( $items as &$item ) {
			$item->$property = self::filter( $connected, $item->$id_key );
		}
		return $items;
	}

	/**
	 * Filter to find the matched items with "mb_origin" value.
	 *
	 * @param array  $list  List of objects.
	 * @param string $value "mb_origin" value.
	 *
	 * @return array
	 */
	protected static function filter( $list, $value ) {
		$filtered = array();
		foreach ( $list as $item ) {
			if ( $value == $item->mb_origin ) {
				$filtered[] = $item;
			}
		}
		return $filtered;
	}
}
