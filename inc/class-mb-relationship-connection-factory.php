<?php
/**
 * The simple connection factory.
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

/**
 * Connection factory class.
 */
class MB_Relationship_Connection_Factory {
	/**
	 * Reference to object factory.
	 *
	 * @var MB_Relationship_Object_Factory
	 */
	protected $object_factory;

	/**
	 * For storing instances.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Constructor.
	 *
	 * @param MB_Relationship_Object_Factory $object_factory Reference to object factory.
	 */
	public function __construct( MB_Relationship_Object_Factory $object_factory ) {
		$this->object_factory = $object_factory;
	}

	/**
	 * Build a new connection.
	 *
	 * @param array $settings Connection settings.
	 *
	 * @return MB_Relationship_Connection
	 */
	public function build( $settings ) {
		$settings = $this->normalize( $settings );

		$connection = new MB_Relationship_Connection( $settings, $this->object_factory );
		$connection->init();

		$this->data[ $settings['id'] ] = $connection;

		return $this->data[ $settings['id'] ];
	}

	/**
	 * Get connections under some conditions.
	 *
	 * @param array $args Custom argument to get meta boxes by.
	 *
	 * @return array
	 */
	public function get_by( $args ) {
		$types = $this->data;
		foreach ( $types as $index => $type ) {
			foreach ( $args as $key => $value ) {
				if ( $type->from[ $key ] !== $value && $type->to[ $key ] !== $value ) {
					unset( $types[ $index ] );
					continue 2; // Skip the meta box loop.
				}
			}
		}

		return $types;
	}

	/**
	 * Normalize connection settings.
	 *
	 * @param array $settings Connection settings.
	 *
	 * @return array
	 */
	protected function normalize( $settings ) {
		$settings = wp_parse_args( $settings, array(
			'id'   => '',
			'from' => '',
			'to'   => '',
		) );
		$default  = array(
			'object_type' => 'post',
			'post_type'   => 'post',
			'query_args'  => array(),
			'meta_box'    => array(
				'hidden'        => false,
				'context'       => 'side',
				'priority'      => 'low',
				'field_title'   => '',
				'empty_message' => '',
			),
		);

		// "From" settings.
		if ( is_string( $settings['from'] ) ) {
			$settings['from'] = array(
				'post_type' => $settings['from'],
			);
		}
		$settings['from']             = array_merge( $default, $settings['from'] );
		$settings['from']['meta_box'] = array_merge( $default['meta_box'], $settings['from']['meta_box'] );

		// "To" settings.
		if ( is_string( $settings['to'] ) ) {
			$settings['to'] = array(
				'post_type' => $settings['to'],
			);
		}
		$settings['to']             = array_merge( $default, $settings['to'] );
		$settings['to']['meta_box'] = array_merge( $default['meta_box'], $settings['to']['meta_box'] );
		return $settings;
	}
}
