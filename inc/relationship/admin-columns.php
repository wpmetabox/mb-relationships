<?php
/**
 * The admin columns class.
 * Registers admin columns for relationships objects.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The admin columns class.
 *
 * @property array  $from From side settings.
 * @property array  $to   To side settings.
 * @property string $id   Relationship ID.
 */
class MBR_Admin_Columns {
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
	 * Constructor.
	 *
	 * @param array                           $settings       Relationship settings.
	 * @param MBR_Object_Factory $object_factory The instance of the API class.
	 */
	public function __construct( $settings, MBR_Object_Factory $object_factory ) {
		$this->settings       = $settings;
		$this->object_factory = $object_factory;
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
	 * Setup hooks to create admin columns.
	 */
	public function init() {
		$this->register_admin_columns( 'from' );
		$this->register_admin_columns( 'to' );
	}

	/**
	 * Register admin columns for each side of a relationship.
	 *
	 * @param  string $side 'from' or 'to'.
	 */
	private function register_admin_columns( $side ) {
		$settings = $this->$side;

		if ( empty( $settings['admin_column'] ) ) {
			return;
		}

		switch ( $settings['object_type'] ) {
			case 'post':
				add_filter( "manage_{$settings['field']['post_type']}_posts_columns", array( $this, "{$side}_columns" ) );
				add_action( "manage_{$settings['field']['post_type']}_posts_custom_column", array( $this, "post_{$side}_column_data" ), 10, 2 );
				break;

			case 'term':
				add_filter( "manage_edit-{$settings['field']['taxonomy']}_columns", array( $this, "{$side}_columns" ) );
				add_filter( "manage_{$settings['field']['taxonomy']}_custom_column", array( $this, "{$side}_column_data" ), 10, 3 );
				break;

			case 'user':
				add_filter( 'manage_users_columns', array( $this, "{$side}_columns" ) );
				add_filter( 'manage_users_custom_column', array( $this, "{$side}_column_data" ), 10, 3 );
				break;
		}
	}

	/**
	 * Add admin columns for 'from' side.
	 *
	 * @param  array $columns Existing columns.
	 * @return array
	 */
	public function from_columns( $columns ) {
		return $this->register_columns( $columns, 'from' );
	}

	/**
	 * Add admin columns for 'to' side.
	 *
	 * @param  array $columns Existing columns.
	 * @return array
	 */
	public function to_columns( $columns ) {
		return $this->register_columns( $columns, 'to' );
	}

	/**
	 * Display column data for posts on 'from' side.
	 *
	 * @param  string $column    Column ID.
	 * @param  int    $object_id Object ID.
	 */
	public function post_from_column_data( $column, $object_id ) {
		if ( $this->settings['id'] . '_to' !== $column ) {
			return;
		}

		echo $this->get_column_data( $object_id, $this->to['object_type'], 'from' );
	}

	/**
	 * Display column data for terms and users on 'from' side.
	 *
	 * @param  string $column    Column ID.
	 * @param  int    $object_id Object ID.
	 */
	public function from_column_data( $content, $column, $object_id ) {
		if ( $this->settings['id'] . '_to' !== $column ) {
			return $content;
		}

		return $this->get_column_data( $object_id, $this->to['object_type'], 'from' );
	}

	/**
	 * Display column data for posts on 'to' side.
	 *
	 * @param  string $column    Column ID.
	 * @param  int    $object_id Object ID.
	 */
	public function post_to_column_data( $column, $object_id ) {
		if ( $this->settings['id'] . '_from' !== $column ) {
			return;
		}

		echo $this->get_column_data( $object_id, $this->from['object_type'], 'to' );
	}

	/**
	 * Display column data for terms and users on 'to' side.
	 *
	 * @param  string $column    Column ID.
	 * @param  int    $object_id Object ID.
	 */
	public function to_column_data( $content, $column, $object_id ) {
		if ( $this->settings['id'] . '_from' !== $column ) {
			return $content;
		}

		return $this->get_column_data( $object_id, $this->from['object_type'], 'to' );
	}

	/**
	 * Register admin columns.
	 *
	 * @param  array  $columns Existing columns.
	 * @param  string $side    'from' or 'to'.
	 * @return array
	 */
	private function register_columns( $columns, $side ) {
		$config    = $this->$side['admin_column'];
		$connected = 'from' === $side ? 'to' : 'from';
		$title     = $this->$side['meta_box']['title'];
		$id        = "{$this->settings['id']}_{$connected}";

		if ( true === $config ) {
			$this->add_column( $columns, $id, $title );
			return $columns;
		}

		// If position is specified.
		if ( is_string( $config ) ) {
			$config                    = strtolower( $config );
			list( $position, $target ) = array_map( 'trim', explode( ' ', $config . ' ' ) );
			$this->add_column( $columns, $id, $title, $position, $target );
			return $columns;
		}

		// If an array of configuration is specified.
		$config                    = wp_parse_args(
			$config,
			array(
				'position' => '',
				'title'    => $title,
			)
		);
		list( $position, $target ) = array_map( 'trim', explode( ' ', $config['position'] . ' ' ) );
		$this->add_column( $columns, $id, $config['title'], $position, $target );

		return $columns;
	}

	/**
	 * Add a new column.
	 *
	 * @param array  $columns  Array of columns.
	 * @param string $id       New column ID.
	 * @param string $title    New column title.
	 * @param string $position New column position. Empty to not specify the position. Could be 'before', 'after' or 'replace'.
	 * @param string $target   The target column. Used with combination with $position.
	 */
	private function add_column( &$columns, $id, $title, $position = '', $target = '' ) {
		// Just add new column.
		if ( ! $position ) {
			$columns[ $id ] = $title;
			return;
		}

		// Add new column in a specific position.
		$new = array();
		switch ( $position ) {
			case 'replace':
				foreach ( $columns as $key => $value ) {
					if ( $key === $target ) {
						$new[ $id ] = $title;
					} else {
						$new[ $key ] = $value;
					}
				}
				break;
			case 'before':
				foreach ( $columns as $key => $value ) {
					if ( $key === $target ) {
						$new[ $id ] = $title;
					}
					$new[ $key ] = $value;
				}
				break;
			case 'after':
				foreach ( $columns as $key => $value ) {
					$new[ $key ] = $value;
					if ( $key === $target ) {
						$new[ $id ] = $title;
					}
				}
				break;
			default:
				return;
		}
		$columns = $new;
	}

	/**
	 * Get column data.
	 *
	 * @param  int    $object_id   Object ID.
	 * @param  string $object_type Object type.
	 * @param  string $direction   Direction.
	 */
	private function get_column_data( $object_id, $object_type, $direction ) {
		$method = "get_{$object_type}_items";
		$items  = $this->$method( $object_id, $direction );
		if ( empty( $items ) ) {
			return '';
		}

		$object = $this->object_factory->build( $object_type );
		$items  = array_map( array( $object, 'render' ), $items );

		return implode( '<br>', $items );
	}

	/**
	 * Get connected posts.
	 *
	 * @param  int    $object_id Object ID.
	 * @param  string $direction Direction.
	 * @return array
	 */
	private function get_post_items( $object_id, $direction ) {
		$query = new WP_Query(
			array(
				'relationship'        => array(
					'id'       => $this->id,
					$direction => $object_id,
				),
				'nopaging'            => true,
				'ignore_sticky_posts' => true,
			)
		);
		return $query->posts;
	}

	/**
	 * Get connected terms.
	 *
	 * @param  int    $object_id Object ID.
	 * @param  string $direction Direction.
	 * @return array
	 */
	private function get_term_items( $object_id, $direction ) {
		return get_terms(
			array(
				'hide_empty'   => false,
				'relationship' => array(
					'id'       => $this->settings['id'],
					$direction => $object_id,
				),
			)
		);
	}

	/**
	 * Get connected users.
	 *
	 * @param  int    $object_id Object ID.
	 * @param  string $direction Direction.
	 * @return array
	 */
	private function get_user_items( $object_id, $direction ) {
		return get_users(
			array(
				'relationship' => array(
					'id'       => $this->settings['id'],
					$direction => $object_id,
				),
			)
		);
	}
}
