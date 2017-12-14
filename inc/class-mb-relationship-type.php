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
	}

	/**
	 * Create meta boxes for relationship, using Meta Box API.
	 */
	public function create_meta_boxes() {
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
		$meta_boxes[] = $this->parse_meta_box( "{$this->args['id']}_relationship_from", $this->args['from'] );
		$meta_boxes[] = $this->parse_meta_box( "{$this->args['id']}_relationship_to", $this->args['to'] );

		return $meta_boxes;
	}

	/**
	 * Normalize type settings.
	 *
	 * @param array $args Type settings.
	 *
	 * @return array
	 */
	private function normalize( $args ) {
		$args = wp_parse_args( $args, array(
			'id'    => '',
			'table' => '',
			'from'  => '',
			'to'    => '',
		) );
		// Short form.
		if ( is_string( $args['from'] ) ) {
			$args['from'] = array(
				'object_type' => 'post',
				'post_type'   => $args['from'],
				'label'       => __( 'Connected From', 'mb-relationship' ),
			);
		} else {
			$args['from'] = wp_parse_args( $args['from'], array(
				'object_type' => 'post',
				'label'       => __( 'Connected From', 'mb-relationship' ),
			) );
		}
		// Short form.
		if ( is_string( $args['to'] ) ) {
			$args['to'] = array(
				'object_type' => 'post',
				'post_type'   => $args['to'],
				'label'       => __( 'Connect To', 'mb-relationship' ),
			);
		} else {
			$args['to'] = wp_parse_args( $args['to'], array(
				'object_type' => 'post',
				'label'       => __( 'Connect To', 'mb-relationship' ),
			) );
		}
		return $args;
	}

	/**
	 * Parse meta box settings.
	 *
	 * @param string $id   Meta Box ID.
	 * @param array  $args Relationship type settings.
	 *
	 * @return array
	 */
	private function parse_meta_box( $id, $args ) {
		$meta_box = array(
			'id'     => $id,
			'title'  => $args['label'],
			'fields' => array(),
		);
		return $meta_box;
	}
}
