<?php
/**
 * The simple connection factory.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Connection factory class.
 */
class MB_Relationships_Connection_Factory {
	/**
	 * Reference to object factory.
	 *
	 * @var MB_Relationships_Object_Factory
	 */
	protected $object_factory;

	/**
	 * For storing instances.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Temporary filter type.
	 *
	 * @var array
	 */
	private $filter_type;

	/**
	 * Constructor.
	 *
	 * @param MB_Relationships_Object_Factory $object_factory Reference to object factory.
	 */
	public function __construct( MB_Relationships_Object_Factory $object_factory ) {
		$this->object_factory = $object_factory;
	}

	/**
	 * Build a new connection.
	 *
	 * @param array $settings Connection settings.
	 *
	 * @return MB_Relationships_Connection
	 */
	public function build( $settings ) {
		$settings = $this->normalize( $settings );

		$connection = new MB_Relationships_Connection( $settings, $this->object_factory );
		$connection->init();

		$this->data[ $settings['id'] ] = $connection;

		return $this->data[ $settings['id'] ];
	}

	/**
	 * Filter connections by object type.
	 *
	 * @param string $type Object type.
	 *
	 * @return array
	 */
	public function filter_by( $type ) {
		$this->filter_type = $type;
		return array_filter( $this->data, array( $this, 'is_filtered' ) );
	}

	/**
	 * Check if connection has an object type on either side.
	 *
	 * @param MB_Relationships_Connection $connection Connection object.
	 *
	 * @return bool
	 */
	protected function is_filtered( MB_Relationships_Connection $connection ) {
		return $connection->has_object_type( $this->filter_type );
	}

	/**
	 * Normalize connection settings.
	 *
	 * @param array $settings Connection settings.
	 *
	 * @return array
	 */
	protected function normalize( $settings ) {
		$settings         = wp_parse_args( $settings, array(
			'id'   => '',
			'from' => '',
			'to'   => '',
		) );
		$settings['from'] = $this->normalize_side( $settings['from'] );
		$settings['to']   = $this->normalize_side( $settings['to'] );

		return $settings;
	}

	/**
	 * Normalize settings for a "from" or "to" side.
	 *
	 * @param array|string $settings Array of settings or post type (string) for short.
	 *
	 * @return array
	 */
	protected function normalize_side( $settings ) {
		$default = array(
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

		if ( is_string( $settings ) ) {
			$settings = array(
				'post_type' => $settings,
			);
		}
		$settings             = array_merge( $default, $settings );
		$settings['meta_box'] = array_merge( $default['meta_box'], $settings['meta_box'] );

		return $settings;
	}
}
