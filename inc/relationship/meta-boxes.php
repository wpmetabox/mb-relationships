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
			$meta_boxes[] = $this->parse_meta_box( 'from' );
		}
		if ( ! $this->to['meta_box']['hidden'] ) {
			$meta_boxes[] = $this->parse_meta_box( 'to' );
		}

		return $meta_boxes;
	}

	/**
	 * Parse meta box settings.
	 *
	 * @param  string $source "from" or "to".
	 * @return array
	 */
	private function parse_meta_box( $source ) {
		$target        = 'from' === $source ? 'to' : 'from';
		$source_object = "{$source}_object";
		$target_object = "{$target}_object";

		$field                 = $this->{$target}['field'];
		$field['type']         = $this->{$target_object}->get_field_type();
		$field['id']           = "{$this->id}_{$target}";
		$field['clone']        = true;
		$field['sort_clone']   = true;
		$field['relationship'] = true;

		$meta_box                 = $this->{$source}['meta_box'];
		$meta_box['id']           = "{$this->id}_relationships_{$target}";
		$meta_box['storage_type'] = 'relationships_table';
		$meta_box['fields']       = array( $field );
		$meta_box                 = array_merge( $meta_box, $this->{$source_object}->get_meta_box_settings( $this->{$source} ) );

		return $meta_box;
	}
}