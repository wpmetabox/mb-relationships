<?php
/**
 * The relationship type class.
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

/**
 * The relationship type class.
 */
class MB_Relationship_Type {
	/**
	 * Store the relationship settings.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Store the API object.
	 *
	 * @var MB_Relationship_API
	 */
	protected $api;

	/**
	 * The object that connects "from".
	 *
	 * @var MB_Relationship_Object_Interface
	 */
	protected $from_object;

	/**
	 * The object that connects "to".
	 *
	 * @var MB_Relationship_Object_Interface
	 */
	protected $to_object;

	/**
	 * Register a relationship type.
	 *
	 * @param array               $args Type settings.
	 * @param MB_Relationship_API $api  The instance of the API class.
	 */
	public function __construct( $args, MB_Relationship_API $api ) {
		$this->args        = $this->normalize( $args );
		$this->api         = $api;
		$this->from_object = $this->api->factory->build( $this->args['from']['object_type'] );
		$this->to_object   = $this->api->factory->build( $this->args['to']['object_type'] );

		$this->setup_hooks();
	}

	/**
	 * Normalize type settings.
	 *
	 * @param array $args Type settings.
	 *
	 * @return array
	 */
	protected function normalize( $args ) {
		$args               = wp_parse_args( $args, array(
			'id'   => '',
			'from' => '',
			'to'   => '',
		) );
		$connection_default = array(
			'object_type' => 'post',
			'post_type'   => 'post',
			'query_args'  => array(),
			'meta_box'    => array(
				'hidden'      => false,
				'context'     => 'side',
				'priority'    => 'low',
				'field_title' => '',
			),
		);
		// "From" settings.
		if ( is_string( $args['from'] ) ) {
			$args['from'] = array(
				'post_type' => $args['from'],
			);
		}
		$args['from']             = array_merge( $connection_default, $args['from'] );
		$args['from']['meta_box'] = array_merge( $connection_default['meta_box'], $args['from']['meta_box'] );

		// "To" settings.
		if ( is_string( $args['to'] ) ) {
			$args['to'] = array(
				'post_type' => $args['to'],
			);
		}
		$args['to']             = array_merge( $connection_default, $args['to'] );
		$args['to']['meta_box'] = array_merge( $connection_default['meta_box'], $args['to']['meta_box'] );
		return $args;
	}

	/**
	 * Setup hooks to create meta boxes for relationship, using Meta Box API.
	 */
	protected function setup_hooks() {
		add_filter( 'rwmb_meta_boxes', array( $this, 'register_meta_boxes' ) );
	}

	/**
	 * Register 2 meta boxes for "From" and "To" connections.
	 *
	 * @param array $meta_boxes Meta boxes array.
	 *
	 * @return array
	 */
	public function register_meta_boxes( $meta_boxes ) {
		if ( ! $this->args['from']['meta_box']['hidden'] ) {
			$meta_boxes[] = $this->parse_meta_box_from();
		}
		if ( ! $this->args['to']['meta_box']['hidden'] ) {
			$meta_boxes[] = $this->parse_meta_box_to();
		}

		return $meta_boxes;
	}

	/**
	 * Parse meta box for "from" object.
	 *
	 * @return array
	 */
	protected function parse_meta_box_from() {
		$field         = $this->to_object->get_field_settings( $this->args['to'] );
		$field['id']   = "{$this->args['id']}_to";
		$field['name'] = $this->args['from']['meta_box']['field_title'];

		$meta_box = array(
			'id'           => "{$this->args['id']}_relationship_to",
			'title'        => $this->args['from']['meta_box']['label'],
			'storage_type' => 'relationship_table',
			'fields'       => array( $field ),
		);
		$meta_box = array_merge( $meta_box, $this->from_object->get_meta_box_settings( $this->args['from'] ) );
		return $meta_box;
	}

	/**
	 * Parse meta box for "to" object.
	 *
	 * @return array
	 */
	protected function parse_meta_box_to() {
		$meta_box = array(
			'id'     => "{$this->args['id']}_relationship_from",
			'title'  => $this->args['to']['meta_box']['label'],
			'fields' => array(
				array(
					'name'     => $this->args['to']['meta_box']['field_title'],
					'type'     => 'custom_html',
					'callback' => array( $this, 'get_connected_from' ),
				),
			),
		);
		$meta_box = array_merge( $meta_box, $this->to_object->get_meta_box_settings( $this->args['to'] ) );
		return $meta_box;
	}

	/**
	 * Output the list of connected from items.
	 *
	 * @return string
	 */
	public function get_connected_from() {
		$items = $this->api->get_connected_to( $this->args['id'], $this->to_object->get_current_id() );
		if ( empty( $items ) ) {
			return '';
		}
		$output = '<ul class="mb-relationship-from-items">';
		foreach ( $items as $item ) {
			$output .= '<li class="mb-relationship-from-item">' . $this->from_object->get_link( $item ) . '</li>';
		}
		$output .= '</ul>';
		return $output;
	}
}
