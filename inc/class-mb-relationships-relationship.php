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
 *
 * @property array  $from From side settings.
 * @property array  $to   To side settings.
 * @property string $id   Relationship ID.
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
	 * Check if 2 objects has a relationship.
	 *
	 * @param int $from From object ID.
	 * @param int $to   To object ID.
	 *
	 * @return bool
	 */
	public function has( $from, $to ) {
		global $wpdb;

		$rel_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `ID` FROM {$wpdb->mb_relationships} WHERE `from`=%d AND `to`=%d AND `type`=%s",
				$from,
				$to,
				$this->id
			)
		);

		return (bool) $rel_id;
	}

	/**
	 * Add a relationship for 2 objects.
	 *
	 * @param int $from From object ID.
	 * @param int $to   To object ID.
	 *
	 * @return bool
	 */
	public function add( $from, $to ) {
		global $wpdb;

		if ( $this->has( $from, $to ) ) {
			return false;
		}

		return $wpdb->insert(
			$wpdb->mb_relationships,
			array(
				'from' => $from,
				'to'   => $to,
				'type' => $this->id,
			),
			array(
				'%d',
				'%d',
				'%s',
			)
		);
	}

	/**
	 * Delete a relationship for 2 objects.
	 *
	 * @param int $from From object ID.
	 * @param int $to   To object ID.
	 *
	 * @return bool
	 */
	public function delete( $from, $to ) {
		global $wpdb;

		return $wpdb->delete(
			$wpdb->mb_relationships,
			array(
				'from' => $from,
				'to'   => $to,
				'type' => $this->id,
			)
		);
	}

	/**
	 * Get relationship object types.
	 *
	 * @param string $side "from" or "to".
	 *
	 * @return string
	 */
	public function get_object_type( $side ) {
		return $this->{$side}['object_type'];
	}

	/**
	 * Check if the relationship has an object type on either side.
	 *
	 * @param mixed $type Object type.
	 *
	 * @return bool
	 */
	public function has_object_type( $type ) {
		return $type === $this->get_object_type( 'from' ) || $type === $this->get_object_type( 'to' );
	}

	/**
	 * Get the database ID field of "from" or "to" object.
	 *
	 * @param string $side "from" or "to".
	 *
	 * @return string
	 */
	public function get_db_field( $side ) {
		$key = $side . '_object';

		return $this->$key->get_db_field();
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
	protected function parse_meta_box_to() {
		$field         = $this->from_object->get_field_settings( $this->from );
		$field['id']   = "{$this->id}_from";
		$field['name'] = $this->to['meta_box']['field_title'];

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
