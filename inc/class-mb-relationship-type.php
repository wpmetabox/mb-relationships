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
	 * Register a relationship type.
	 *
	 * @param array $args Type settings.
	 */
	public function __construct( $args ) {
		$this->args = $this->normalize( $args );

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
			'id'    => '',
			'table' => '',
			'from'  => '',
			'to'    => '',
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
		$to       = $this->args['to'];
		$meta_box = array(
			'id'       => "{$this->args['id']}_relationship_to",
			'title'    => $to['label'],
			'context'  => $this->args['to']['context'],
			'priority' => $this->args['to']['priority'],
			'fields'   => array(),
		);
		$field    = array(
			'id'         => '_relationship_to',
			'clone'      => true,
			'sort_clone' => true,
		);
		switch ( $to['object_type'] ) {
			case 'post':
				$field['type']       = 'post';
				$field['post_type']  = $to['post_type'];
				$field['query_args'] = $to['query_args'];
				break;
			case 'taxonomy':
				$field['type']       = 'taxonomy_advanced';
				$field['taxonomy']   = $to['taxonomy'];
				$field['query_args'] = $to['query_args'];
				break;
			case 'user':
				$field['type']       = 'user';
				$field['query_args'] = $to['query_args'];
				break;
		}
		$meta_box['fields'][] = $field;
		return $meta_box;
	}

	/**
	 * Output the list of connected from items.
	 *
	 * @return string
	 */
	public function get_connected_from() {
		return 'So good';
	}
}
