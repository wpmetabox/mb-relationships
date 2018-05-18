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
	 * The wpdb object.
	 *
	 * @var wpdb
	 */
	protected $db;
	
	protected $from_type;
	
	protected $to_type;

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

		global $wpdb;
		$this->db = $wpdb;
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
		$rel_id = $this->db->get_var( $this->db->prepare(
			"SELECT `ID` FROM {$this->db->mb_relationships} WHERE `from`=%d AND `to`=%d AND `type`=%s",
			$from, $to, $this->id
		) );

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
		if ( $this->has( $from, $to ) ) {
			return false;
		}

		return $this->db->insert(
			$this->db->mb_relationships,
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
		if ( ! $this->has( $from, $to ) ) {
			return false;
		}

		return $this->db->delete(
			$this->db->mb_relationships,
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
		return $this->$side['object_type'];
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
		
		$this->from_type = $this->settings['from']['object_type'];
		$this->to_type = $this->settings['to']['object_type'];
		
		switch ( $this->from_type ) {
			case 'post':
				$post_type = $this->settings['from']['post_type'];
				if ( 'post' === $post_type ) {
					add_filter( 'manage_posts_columns', array( $this, 'post_to_columns' ) );
					add_action( 'manage_posts_custom_column', array( $this, 'post_to_column_data' ), 10, 2 );
				} elseif ( 'page' === $post_type ) {
					add_filter( 'manage_pages_columns', array( $this, 'post_to_columns' ) );
					add_action( 'manage_pages_custom_column', array( $this, 'post_to_column_data' ), 10, 2 );
				} else {
					add_filter( "manage_{$post_type}_posts_columns", array( $this, 'post_to_columns' ) );
					add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'post_to_column_data' ), 10, 2 );
				}
				
		}
		
		switch ( $this->to_type ) {
			case 'post':
				$post_type = $this->settings['to']['post_type'];
				if ( 'post' === $post_type ) {
					add_filter( 'manage_posts_columns', array( $this, 'post_from_columns' ) );
					add_action( 'manage_posts_custom_column', array( $this, 'post_from_column_data' ), 10, 2 );
				} elseif ( 'page' === $post_type ) {
					add_filter( 'manage_pages_columns', array( $this, 'post_from_columns' ) );
					add_action( 'manage_pages_custom_column', array( $this, 'post_from_column_data' ), 10, 2 );
				} else {
					add_filter( "manage_{$post_type}_posts_columns", array( $this, 'post_from_columns' ) );
					add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'post_from_column_data' ), 10, 2 );
				}
				
		}
		// echo '<pre>'; print_r( $this->settings ); echo '</pre>';
	}
	
	public function post_from_columns( $columns ) {
		$columns['from'] = __( 'From', 'mb-relationships' );
		return $columns;
	}
	
	public function post_from_column_data( $column_name, $post_id ) {
		if ( 'from' !== $column_name ) {
			return;
		}
		
		switch ( $this->from_type ) {
			case 'post':
				$related = get_posts( array(
					'relationship' => array(
						'id' => $this->settings['id'],
						'to' => $post_id,
					),
					'nopaging'     => true,
					'fields'       => 'ids',
				) );
				if ( $related ) {
					echo '<ul>';
					foreach ( $related as $value ) {
						printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( get_permalink( $value ) ), esc_html( get_the_title( $value ) ) );
					}
					echo '</ul>';
				}
		}
	}
	
	public function post_to_columns( $columns ) {
		$columns['to'] = __( 'To', 'mb-relationships' );
		return $columns;
	}
	
	public function post_to_column_data( $column_name, $post_id ) {
		if ( 'to' !== $column_name ) {
			return;
		}
		
		switch ( $this->from_type ) {
			case 'post':
				$related = get_posts( array(
					'relationship' => array(
						'id'   => $this->settings['id'],
						'from' => $post_id,
					),
					'nopaging'     => true,
					'fields'       => 'ids',
				) );
				if ( $related ) {
					echo '<ul>';
					foreach ( $related as $value ) {
						printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( get_permalink( $value ) ), esc_html( get_the_title( $value ) ) );
					}
					echo '</ul>';
				}
		}
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
