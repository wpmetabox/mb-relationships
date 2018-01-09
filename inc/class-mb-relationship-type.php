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
		if ( ! $this->args['from']['hide_meta_box'] ) {
			$meta_boxes[] = $this->parse_from_meta_box();
		}
		if ( ! $this->args['to']['hide_meta_box'] ) {
			$meta_boxes[] = $this->parse_to_meta_box();
		}

		return $meta_boxes;
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
			'object_type'   => 'post',
			'post_type'     => 'post',
			'query_args'    => array(),
			'hide_meta_box' => false,
			'context'       => 'side',
			'priority'      => 'low',
		);
		// Short form.
		if ( is_string( $args['from'] ) ) {
			$args['from'] = array(
				'post_type' => $args['from'],
			);
		}
		$args['from'] = array_merge( $connection_default, array(
			'label' => __( 'Connected From', 'mb-relationship' ),
		), $args['from'] );
		// Short form.
		if ( is_string( $args['to'] ) ) {
			$args['to'] = array(
				'post_type' => $args['to'],
			);
		}
		$args['to'] = array_merge( $connection_default, array(
			'label' => __( 'Connect To', 'mb-relationship' ),
		), $args['to'] );
		return $args;
	}

	/**
	 * Parse "from" meta box.
	 *
	 * @return array
	 */
	protected function parse_from_meta_box() {
		$meta_box = array(
			'id'       => "{$this->args['id']}_relationship_from",
			'title'    => $this->args['from']['label'],
			'context'  => $this->args['from']['context'],
			'priority' => $this->args['from']['priority'],
			'fields'   => array(
				array(
					'type'     => 'custom_html',
					'callback' => array( $this, 'get_connected_from' ),
				),
			),
		);
		return $meta_box;
	}

	/**
	 * Parse "to" meta box.
	 *
	 * @return array
	 */
	protected function parse_to_meta_box() {
		$to = $this->args['to'];

		$field       = $this->to_object->get_query_args( $to );
		$field['id'] = "{$this->args['id']}_to";

		return array(
			'id'           => "{$this->args['id']}_relationship_to",
			'title'        => $to['label'],
			'context'      => $this->args['to']['context'],
			'priority'     => $this->args['to']['priority'],
			'storage_type' => 'relationship_table',
			'fields'       => array( $field ),
		);
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
