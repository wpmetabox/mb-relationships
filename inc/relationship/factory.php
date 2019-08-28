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
class MBR_Relationship_Factory {
	/**
	 * Reference to object factory.
	 *
	 * @var MBR_Object_Factory
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
	 * @param MBR_Object_Factory $object_factory Reference to object factory.
	 */
	public function __construct( MBR_Object_Factory $object_factory ) {
		$this->object_factory = $object_factory;
	}

	/**
	 * Build a new relationship.
	 *
	 * @param array $settings Relationship settings.
	 *
	 * @return MBR_Relationship
	 */
	public function build( $settings ) {
		$settings = $this->normalize( $settings );

		$relationship = new MBR_Relationship( $settings, $this->object_factory );
		$admin_columns = new MBR_Admin_Columns( $settings, $this->object_factory );
		$admin_columns->init();
		$meta_boxes = new MBR_Meta_Boxes( $settings, $this->object_factory );
		$meta_boxes->init();

		$this->data[ $settings['id'] ] = $relationship;

		return $this->data[ $settings['id'] ];
	}

	/**
	 * Get a relationship object.
	 *
	 * @param string $id Relationship ID.
	 *
	 * @return MBR_Relationship
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
	 * @param MBR_Relationship $relationship Relationship object.
	 *
	 * @return bool
	 */
	protected function is_filtered( MBR_Relationship $relationship ) {
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
				'id'         => '',
				'from'       => '',
				'to'         => '',
				'label_from' => 'Connects From', // Translation is done in normalize_side
				'label_to'   => 'Connects To', // Translation is done in normalize_side
			)
		);
		$settings['from'] = $this->normalize_side( $settings['from'], 'from', $settings['label_from'] );
		$settings['to']   = $this->normalize_side( $settings['to'], 'to', $settings['label_to'] );

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
	protected function normalize_side( $settings, $direction, $label ) {
		$title   = __( $label, 'mb-relationships' );
		$default = array(
			'object_type' => 'post',
			'post_type'   => 'post',
			'reciprocal'  => false,
			'query_args'  => array(),
			'meta_box'    => array(
				'hidden'            => false,
				'autosave'          => false,
				'closed'            => false,
				'context'           => 'side',
				'priority'          => 'low',
				'title'             => $title,
				'field_title'       => '',
				'field_placeholder' => '',
				'empty_message'     => __( 'No connections', 'mb-relationships' ),
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
