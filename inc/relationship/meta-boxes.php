<?php
/**
 * The meta boxes class.
 * Registers meta boxes for relationships objects.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The meta boxes class.
 *
 * @property array  $from From side settings.
 * @property array  $to   To side settings.
 * @property string $id   Relationship ID.
 */
class MBR_Meta_Boxes {
	/**
	 * The relationship settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * The object factory.
	 *
	 * @var MBR_Object_Factory
	 */
	private $object_factory;

	/**
	 * The object that connects "from".
	 *
	 * @var MBR_Object_Interface
	 */
	private $from_object;

	/**
	 * The object that connects "to".
	 *
	 * @var MBR_Object_Interface
	 */
	private $to_object;

	/**
	 * Constructor.
	 *
	 * @param array                           $settings       Relationship settings.
	 * @param MBR_Object_Factory $object_factory The instance of the API class.
	 */
	public function __construct( $settings, MBR_Object_Factory $object_factory ) {
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
	 * Setup hooks to create meta boxes for relationships, using Meta Box API.
	 */
	public function init() {
		add_filter( 'rwmb_meta_boxes', array( $this, 'register_meta_boxes' ) );
	}

	/**
	 * Register 2 meta boxes for "from" and "to" sides.
	 *
	 * @param array $meta_boxes Meta boxes array.
	 *
	 * @return array
	 */
	public function register_meta_boxes( $meta_boxes ) {
		if ( ! $this->from['meta_box']['hidden'] ) {
			$meta_boxes[] = $this->parse_meta_box_from();
		}
		if ( ! $this->to['meta_box']['hidden'] ) {
			$meta_boxes[] = $this->parse_meta_box_to();
		}

		return $meta_boxes;
	}

	/**
	 * Parse meta box for "from" object.
	 *
	 * @return array
	 */
	private function parse_meta_box_from() {
		$field         = $this->to_object->get_field_settings( $this->to );
		$field['id']   = "{$this->id}_to";
		$field['name'] = $this->from['meta_box']['field_title'];
		if ( '' !== $this->from['meta_box']['field_placeholder']) {
			$field['placeholder'] = $this->from['meta_box']['field_placeholder'];
		}

		$meta_box = array(
			'id'           => "{$this->id}_relationships_to",
			'title'        => $this->from['meta_box']['title'],
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
	private function parse_meta_box_to() {
		$field         = $this->from_object->get_field_settings( $this->from );
		$field['id']   = "{$this->id}_from";
		$field['name'] = $this->to['meta_box']['field_title'];
		if ( '' !== $this->from['meta_box']['field_placeholder']) {
			$field['placeholder'] = $this->from['meta_box']['field_placeholder'];
		}

		$meta_box = array(
			'id'           => "{$this->id}_relationships_from",
			'title'        => $this->to['meta_box']['title'],
			'storage_type' => 'relationships_table',
			'fields'       => array( $field ),
		);
		$meta_box = array_merge( $meta_box, $this->to_object->get_meta_box_settings( $this->to ) );

		return $meta_box;
	}
}