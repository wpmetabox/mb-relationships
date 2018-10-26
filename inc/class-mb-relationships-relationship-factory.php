<?php
/**
 * The simple relationship factory.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Relationship factory class.
 */
class MB_Relationships_Relationship_Factory {
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
	 * Build a new relationship.
	 *
	 * @param array $settings Relationship settings.
	 *
	 * @return MB_Relationships_Relationship
	 */
	public function build( $settings ) {
		$settings = $this->normalize( $settings );

		$relationship = new MB_Relationships_Relationship( $settings, $this->object_factory );
		$relationship->init();

		$this->data[ $settings['id'] ] = $relationship;

		return $this->data[ $settings['id'] ];
	}

	/**
	 * Get a relationship object.
	 *
	 * @param string $id Relationship ID.
	 *
	 * @return MB_Relationships_Relationship
	 */
	public function get( $id ) {
		return isset( $this->data[ $id ] ) ? $this->data[ $id ] : null;
	}

	/**
	 * Filter relationships by object type.
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
	 * Check if relationship has an object type on either side.
	 *
	 * @param MB_Relationships_Relationship $relationship Relationship object.
	 *
	 * @return bool
	 */
	protected function is_filtered( MB_Relationships_Relationship $relationship ) {
		return $relationship->has_object_type( $this->filter_type );
	}

	/**
	 * Normalize relationship settings.
	 *
	 * @param array $settings Relationship settings.
	 *
	 * @return array
	 */
	protected function normalize( $settings ) {
		$settings         = wp_parse_args(
			$settings,
			array(
				'id'   => '',
				'from' => '',
				'to'   => '',
			)
		);
		$settings['from'] = $this->normalize_side( $settings['from'], 'from' );
		$settings['to']   = $this->normalize_side( $settings['to'], 'to' );

		return $settings;
	}

	/**
	 * Normalize settings for a "from" or "to" side.
	 *
	 * @param array|string $settings  Array of settings or post type (string) for short.
	 * @param string       $direction Relationship direction.
	 *
	 * @return array
	 */
	protected function normalize_side( $settings, $direction ) {
		$title   = 'from' === $direction ? __( 'Connects To', 'mb-relationships' ) : __( 'Connected From', 'mb-relationship' );
		$default = array(
			'object_type' => 'post',
			'post_type'   => 'post',
			'query_args'  => array(),
			'meta_box'    => array(
				'hidden'        => false,
				'autosave'      => false,
				'closed'        => false,
				'context'       => 'side',
				'priority'      => 'low',
				'title'         => $title,
				'field_title'   => '',
				'empty_message' => __( 'No connections', 'mb-relationships' ),
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
