<?php
/**
 * Normalizes the query arguments.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Normalizer class.
 */
class MB_Relationships_Query_Normalizer {
	/**
	 * The relationship factory.
	 *
	 * @var MB_Relationships_Relationship_Factory
	 */
	protected $factory;

	/**
	 * Constructor
	 *
	 * @param MB_Relationships_Relationship_Factory $factory The relationship factory.
	 */
	public function __construct( MB_Relationships_Relationship_Factory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Normalize relationship query args.
	 *
	 * @param array $args Query arguments.
	 */
	public function normalize( &$args ) {
		$direction        = isset( $args['from'] ) ? 'from' : 'to';
		$relationship     = $this->factory->get( $args['id'] );
		$args['id_field'] = $relationship->get_db_field( $direction );

		$args['direction'] = $direction;
		$items             = $args[ $direction ];
		$items             = $this->get_ids( $items, $args['id_field'] );
		$args['items']     = $items;

		unset( $args[ $direction ] );
	}

	/**
	 * Get object IDs from list of objects.
	 *
	 * @param array  $items    Array of objects or IDs.
	 * @param string $id_field Object ID field.
	 *
	 * @return array
	 */
	protected function get_ids( $items, $id_field ) {
		$items = (array) $items;
		$first = reset( $items );
		return is_numeric( $first ) ? $items : wp_list_pluck( $items, $id_field );
	}
}
