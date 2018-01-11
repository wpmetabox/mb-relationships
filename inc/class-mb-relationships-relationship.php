<?php
/**
 * The relationship class.
 * Registers meta boxes and custom fields for objects, displays and handles data.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The relationship class.
 */
class MB_Relationships_Relationship {
	/**
	 * The relationship settings.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * The object that connects "from".
	 *
	 * @var MB_Relationships_Object_Interface
	 */
	protected $from_object;

	/**
	 * The object that connects "to".
	 *
	 * @var MB_Relationships_Object_Interface
	 */
	protected $to_object;

	/**
	 * Register a relationship.
	 *
	 * @param array                           $settings       Relationship settings.
	 * @param MB_Relationships_Object_Factory $object_factory The instance of the API class.
	 */
	public function __construct( $settings, MB_Relationships_Object_Factory $object_factory ) {
		$this->settings    = $settings;
		$this->from_object = $object_factory->build( $this->from['object_type'] );
		$this->to_object   = $object_factory->build( $this->to['object_type'] );
	}

	/**
	 * Magic method to quick access to relationship settings.
	 *
	 * @param string $name Setting name.
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : '';
	}

	/**
	 * Check if the relationship has an object type on either side.
	 *
	 * @param mixed $type Object type.
	 *
	 * @return bool
	 */
	public function has_object_type( $type ) {
		return $this->from['object_type'] === $type || $this->to['object_type'] === $type;
	}

	/**
	 * Setup hooks to create meta boxes for relationships, using Meta Box API.
	 */
	public function init() {
		add_filter( 'rwmb_meta_boxes', array( $this, 'register_meta_boxes' ) );
	}

	/**
	 * Register 2 meta boxes for "From" and "To" relationships.
	 *
	 * @param array $meta_boxes Meta boxes array.
	 *
	 * @return array
	 */
	public function register_meta_boxes( $meta_boxes ) {
		if ( ! $this->from['meta_box']['hidden'] ) {
			$meta_boxes[] = $this->parse_meta_box_from();
		}
		$meta_boxes[] = $this->parse_meta_box_to();

		return $meta_boxes;
	}

	/**
	 * Parse meta box for "from" object.
	 *
	 * @return array
	 */
	protected function parse_meta_box_from() {
		$field         = $this->to_object->get_field_settings( $this->to );
		$field['id']   = "{$this->id}_to";
		$field['name'] = $this->from['meta_box']['field_title'];

		$meta_box = array(
			'id'           => "{$this->id}_relationships_to",
			'title'        => $this->from['meta_box']['label'],
			'storage_type' => 'relationships_table',
			'fields'       => array( $field ),
		);
		$meta_box = array_merge( $meta_box, $this->from_object->get_meta_box_settings( $this->from ) );
		return $meta_box;
	}

	/**
	 * Parse meta box for "to" object.
	 *
	 * @return array
	 */
	protected function parse_meta_box_to() {
		$meta_box = array(
			'id'     => "{$this->id}_relationships_from",
			'title'  => $this->to['meta_box']['label'],
			'fields' => array(
				array(
					'name'     => $this->to['meta_box']['field_title'],
					'type'     => 'custom_html',
					'callback' => array( $this, 'get_connected_to' ),
				),
			),
		);
		$meta_box = array_merge( $meta_box, $this->to_object->get_meta_box_settings( $this->to ) );
		return $meta_box;
	}

	/**
	 * Output the list of connected from items.
	 *
	 * @return string
	 */
	public function get_connected_to() {
		global $wpdb;
		$items = $wpdb->get_col( $wpdb->prepare(
			"SELECT `from` FROM $wpdb->mb_relationships WHERE `to`=%d AND `type`=%s",
			$this->to_object->get_current_id(),
			$this->id
		) );
		if ( empty( $items ) ) {
			return $this->to['meta_box']['empty_message'];
		}
		$output = '<ul class="mb-relationships-from-items">';
		foreach ( $items as $item ) {
			$output .= '<li class="mb-relationships-from-item">' . $this->from_object->get_link( $item ) . '</li>';
		}
		$output .= '</ul>';
		return $output;
	}
}
