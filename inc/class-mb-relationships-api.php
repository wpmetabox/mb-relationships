<?php
/**
 * Public API helper functions.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The API class.
 */
class MB_Relationships_API {
	/**
	 * Reference to relationship factory.
	 *
	 * @var MB_Relationships_Relationship_Factory
	 */
	public static $factory;

	/**
	 * Set relationship factory.
	 *
	 * @param MB_Relationships_Relationship_Factory $factory The object factory.
	 */
	public static function set_relationship_factory( MB_Relationships_Relationship_Factory $factory ) {
		self::$factory = $factory;
	}

	/**
	 * Register a relationship.
	 *
	 * @param array $settings Relationship parameters.
	 *
	 * @return MB_Relationships_Relationship
	 */
	public static function register( $settings ) {
		return self::$factory->build( $settings );
	}

	/**
	 * Get connected items for each object in the list.
	 *
	 * @param array $args       Relationship query arguments.
	 * @param array $query_vars Extra query variables.
	 */
	public static function each_connected( $args, $query_vars = array() ) {
		$args         = wp_parse_args( $args, array(
			'id'       => '',
			'property' => 'connected',
		) );
		$relationship = self::$factory->get( $args['id'] );
		if ( ! $relationship ) {
			return;
		}

		$direction   = isset( $args['from'] ) ? 'from' : 'to';
		$connected   = isset( $args['from'] ) ? 'to' : 'from';
		$object_type = $relationship->get_object_type( $connected );
		$id_key      = $relationship->get_db_field( $direction );

		$query_vars = wp_parse_args( $query_vars, array(
			'relationship' => $args,
		) );
		$items      = array();

		switch ( $object_type ) {
			case 'post':
				$query_vars = wp_parse_args( $query_vars, array(
					'nopaging' => true,
				) );
				$query      = new WP_Query( $query_vars );
				$items      = $query->posts;
				break;
			case 'term':
				$settings   = $relationship->$connected;
				$query_vars = wp_parse_args( $query_vars, array(
					'taxonomy'   => $settings['taxonomy'],
					'hide_empty' => false,
				) );
				$items      = get_terms( $query_vars );
				break;
			case 'user':
				$items = get_users( $query_vars );
				break;
		}

		MB_Relationships_Query::distribute( $args[ $direction ], $items, $args['property'], $id_key );
	}
}
