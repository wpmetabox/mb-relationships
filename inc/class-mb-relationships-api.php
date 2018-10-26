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
	protected static $factory;

	/**
	 * Reference to post query object.
	 *
	 * @var MB_Relationships_Query_Post
	 */
	protected static $post_query;

	/**
	 * Reference to term query object.
	 *
	 * @var MB_Relationships_Query_Term
	 */
	protected static $term_query;

	/**
	 * Reference to user query object.
	 *
	 * @var MB_Relationships_Query_User
	 */
	protected static $user_query;

	/**
	 * Set relationship factory.
	 *
	 * @param MB_Relationships_Relationship_Factory $factory The object factory.
	 */
	public static function set_relationship_factory( MB_Relationships_Relationship_Factory $factory ) {
		self::$factory = $factory;
	}

	/**
	 * Set post query.
	 *
	 * @param MB_Relationships_Query_Post $post_query The post query object.
	 */
	public static function set_post_query( MB_Relationships_Query_Post $post_query ) {
		self::$post_query = $post_query;
	}

	/**
	 * Set term query.
	 *
	 * @param MB_Relationships_Query_Term $term_query The term query object.
	 */
	public static function set_term_query( MB_Relationships_Query_Term $term_query ) {
		self::$term_query = $term_query;
	}

	/**
	 * Set user query.
	 *
	 * @param MB_Relationships_Query_User $user_query The user query object.
	 */
	public static function set_user_query( MB_Relationships_Query_User $user_query ) {
		self::$user_query = $user_query;
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
	 * Check if 2 objects has a relationship.
	 *
	 * @param int    $from From object ID.
	 * @param int    $to   To object ID.
	 * @param string $id   Relationship ID.
	 *
	 * @return bool
	 */
	public static function has( $from, $to, $id ) {
		$relationship = self::$factory->get( $id );
		return $relationship ? $relationship->has( $from, $to ) : false;
	}

	/**
	 * Add a relationship for 2 objects.
	 *
	 * @param int    $from From object ID.
	 * @param int    $to   To object ID.
	 * @param string $id   Relationship ID.
	 *
	 * @return bool
	 */
	public static function add( $from, $to, $id ) {
		$relationship = self::$factory->get( $id );
		return $relationship ? $relationship->add( $from, $to ) : false;
	}

	/**
	 * Delete a relationship for 2 objects.
	 *
	 * @param int    $from From object ID.
	 * @param int    $to   To object ID.
	 * @param string $id   Relationship ID.
	 *
	 * @return bool
	 */
	public static function delete( $from, $to, $id ) {
		$relationship = self::$factory->get( $id );
		return $relationship ? $relationship->delete( $from, $to ) : false;
	}

	/**
	 * Get connected items for each object in the list.
	 *
	 * @param array $args       Relationship query arguments.
	 * @param array $query_vars Extra query variables.
	 */
	public static function each_connected( $args, $query_vars = array() ) {
		$args         = wp_parse_args(
			$args,
			array(
				'id'       => '',
				'property' => 'connected',
			)
		);
		$relationship = self::$factory->get( $args['id'] );
		if ( ! $relationship ) {
			return;
		}

		$direction    = isset( $args['from'] ) ? 'from' : 'to';
		$connected    = isset( $args['from'] ) ? 'to' : 'from';
		$object_type  = $relationship->get_object_type( $connected );
		$id_key       = $relationship->get_db_field( $direction );
		$query_object = $object_type . '_query';

		$items = self::$$query_object->query( $args, $query_vars, $relationship );
		self::distribute( $args[ $direction ], $items, $args['property'], $id_key );
	}

	/**
	 * Get connected items.
	 *
	 * @param array $args Relationship arguments.
	 *
	 * @return array
	 */
	public static function get_connected( $args ) {
		$args         = wp_parse_args(
			$args,
			array(
				'id' => '',
			)
		);
		$relationship = self::$factory->get( $args['id'] );
		if ( ! $relationship ) {
			return array();
		}

		$connected    = isset( $args['from'] ) ? 'to' : 'from';
		$object_type  = $relationship->get_object_type( $connected );
		$query_object = $object_type . '_query';

		return self::$$query_object->query( $args, array(), $relationship );
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
	protected static function distribute( &$items, $connected, $property, $id_key ) {
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
