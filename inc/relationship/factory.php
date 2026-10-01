<?php
/**
 * The simple relationship factory.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Relationship factory class.
 */
class MBR_Relationship_Factory {

	/**
	 * The object factory.
	 *
	 * @var MBR_Object_Factory
	 */
	private $object_factory;

	/**
	 * The registered relationships.
	 *
	 * @var array<string, MBR_Relationship>
	 */
	private $relationships = [];

	/**
	 * Relationships settings.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $relationships_settings = [];

	/**
	 * Filter type.
	 *
	 * @var string
	 */
	private $filter_type;

	/**
	 * Constructor.
	 *
	 * @param MBR_Object_Factory $object_factory Reference to object factory.
	 */
	public function __construct( MBR_Object_Factory $object_factory ) {
		$this->object_factory = $object_factory;
	}

	/**
	 * Build a new relationship.
	 *
	 * @param array $settings Relationship settings.
	 *
	 * @return MBR_Relationship
	 */
	public function build( $settings ) {
		$settings = $this->normalize( $settings );

		$relationship  = new MBR_Relationship( $settings, $this->object_factory );
		$admin_columns = new MBR_Admin_Columns( $settings, $this->object_factory );
		$admin_columns->init();
		$meta_boxes = new MBR_Meta_Boxes( $settings, $relationship );
		$meta_boxes->init();

		$this->relationships[ $settings['id'] ]          = $relationship;
		$this->relationships_settings[ $settings['id'] ] = $settings;

		// hook into post-registration action
		do_action( 'mb_relationships_registered', $settings );

		return $this->relationships[ $settings['id'] ];
	}

	public function get( $id ) {
		return isset( $this->relationships[ $id ] ) ? $this->relationships[ $id ] : null;
	}

	public function get_settings( $id ) {
		return isset( $this->relationships_settings[ $id ] ) ? $this->relationships_settings[ $id ] : null;
	}

	public function all() {
		return $this->relationships;
	}

	public function all_settings() {
		return $this->relationships_settings;
	}

	/**
	 * Filter relationships by object type.
	 *
	 * @param string $type Object type.
	 *
	 * @return array
	 */
	public function filter_by( $type ) {
		$this->filter_type = $type;
		return array_filter( $this->relationships, [ $this, 'is_filtered' ] );
	}

	/**
	 * Check if relationship has an object type on either side.
	 *
	 * @param MBR_Relationship $relationship Relationship object.
	 *
	 * @return bool
	 */
	protected function is_filtered( MBR_Relationship $relationship ) {
		return $relationship->has_object_type( $this->filter_type );
	}

	/**
	 * Normalize relationship settings.
	 *
	 * @param array $settings Relationship settings.
	 *
	 * @return array
	 */
	protected function normalize( $settings ) {
		$settings         = wp_parse_args( $settings, [
			'id'         => '',
			'from'       => '',
			'to'         => '',
			'label_from' => __( 'Connects To', 'mb-relationships' ),
			'label_to'   => __( 'Connected From', 'mb-relationships' ),
			'reciprocal' => false,
		] );
		$settings['from'] = $this->normalize_side( $settings['from'], $settings['label_from'] );
		$settings['to']   = $this->normalize_side( $settings['to'], $settings['label_to'] );

		$settings = apply_filters( 'mb_relationships_settings', $settings );

		return $settings;
	}

	/**
	 * Normalize settings for a "from" or "to" side.
	 *
	 * @param array|string $settings  Array of settings or post type (string) for short.
	 */
	protected function normalize_side( $settings, $label ): array {
		$default = [
			'object_type'          => 'post',
			'has_one_relationship' => false,
			'empty_message'        => __( 'No connections', 'mb-relationships' ),
			'meta_box'             => [
				'title'    => $label,
				'hidden'   => false,
				'context'  => 'side',
				'priority' => 'low',
			],
			'field'                => [
				'type'      => 'post',
				'post_type' => 'post',
			],
		];

		if ( is_string( $settings ) ) {
			$settings = [
				'field' => [
					'post_type' => $settings,
				],
			];
		}

		$settings             = array_merge( $default, $settings );
		$settings['meta_box'] = array_merge( $default['meta_box'], $settings['meta_box'] );
		$settings['field']    = array_merge( $default['field'], $settings['field'] );

		$this->migrate_syntax( $settings );
		$this->normalize_model( $settings );

		// Fixed settings.
		$settings['field']['clone']        = true;
		$settings['field']['sort_clone']   = true;
		$settings['field']['relationship'] = true;

		$settings['meta_box']['storage_type'] = 'relationships_table';

		$this->set_default_field_label( $settings['field'] );

		return $settings;
	}

	/**
	 * Normalize model side settings (new feature — not legacy migrate_syntax).
	 *
	 * @param array $settings Relationship settings for a side.
	 */
	private function normalize_model( &$settings ): void {
		if ( 'model' !== ( $settings['object_type'] ?? '' ) ) {
			return;
		}

		if ( ! class_exists( \MetaBox\CustomTable\ModelField::class ) ) {
			$this->invalidate_model_side(
				$settings,
				__( 'Relationship side with object_type "model" requires the MB Custom Table extension.', 'mb-relationships' )
			);
			return;
		}

		$model = $settings['model'] ?? ( $settings['field']['model'] ?? '' );
		if ( ! $model ) {
			$this->invalidate_model_side(
				$settings,
				__( 'Relationship side with object_type "model" requires a model name.', 'mb-relationships' )
			);
			return;
		}

		if ( isset( $settings['item_title'] ) ) {
			$settings['field']['item_title'] = $settings['item_title'];
			unset( $settings['item_title'] );
		}

		$settings['field']['type']      = 'model';
		$settings['field']['model']     = $model;
		$settings['meta_box']['models'] = [ $model ];

		unset( $settings['model'], $settings['field']['post_type'], $settings['meta_box']['post_types'] );
	}

	/**
	 * Mark a model side as invalid so meta boxes are not registered.
	 *
	 * Uses an internal `invalid` flag instead of overloading public `meta_box.hidden`.
	 */
	private function invalidate_model_side( array &$settings, string $message ): void {
		_doing_it_wrong( __METHOD__, $message, '1.14.0' );
		$settings['invalid']       = true;
		$settings['field']['type'] = '';
		unset( $settings['field']['post_type'], $settings['meta_box']['post_types'], $settings['model'] );
	}

	/**
	 * Migrate from old/simple syntax to the formal one.
	 *
	 * @param  array $settings Relationship settings for a side.
	 */
	private function migrate_syntax( &$settings ): void {
		$meta_box = &$settings['meta_box'];
		$field    = &$settings['field'];

		// General settings.
		if ( ! empty( $meta_box['empty_message'] ) ) {
			$settings['empty_message'] = $meta_box['empty_message'];
			unset( $meta_box['empty_message'] );
		}

		// Field general settings.
		if ( ! empty( $meta_box['field_title'] ) ) {
			$field['name'] = $meta_box['field_title'];
			unset( $meta_box['field_title'] );
		}
		if ( ! empty( $settings['query_args'] ) ) {
			$field['query_args'] = $settings['query_args'];
			unset( $settings['query_args'] );
		}

		// Post.
		if ( ! empty( $settings['post_type'] ) ) {
			$field['post_type'] = $settings['post_type'];
			unset( $settings['post_type'] );
		}
		if ( 'post' === $settings['object_type'] ) {
			$field['type']          = 'post';
			$meta_box['post_types'] = [ $field['post_type'] ];
		}

		// Term.
		if ( ! empty( $settings['taxonomy'] ) ) {
			$field['taxonomy'] = $settings['taxonomy'];
			unset( $settings['taxonomy'] );
		}
		if ( 'term' === $settings['object_type'] ) {
			$field['type']          = 'taxonomy_advanced';
			$meta_box['taxonomies'] = [ $field['taxonomy'] ];
			unset( $field['post_type'] );
		}

		// User.
		if ( 'user' === $settings['object_type'] ) {
			$field['type']    = 'user';
			$meta_box['type'] = 'user';
			unset( $field['post_type'] );
		}
	}

	private function set_default_field_label( array &$field ): void {
		if ( isset( $field['name'] ) ) {
			return;
		}

		if ( $field['type'] === 'user' ) {
			$field['name'] = __( 'Users', 'mb-relationships' );
			return;
		}

		if ( $field['type'] === 'post' ) {
			$post_type_object = get_post_type_object( $field['post_type'] );
			if ( ! $post_type_object ) {
				return;
			}
			$field['name'] = $post_type_object->labels->name;
			return;
		}

		if ( $field['type'] === 'taxonomy_advanced' ) {
			$taxonomy_object = get_taxonomy( $field['taxonomy'] );
			if ( ! $taxonomy_object ) {
				return;
			}
			$field['name'] = $taxonomy_object->labels->name;
			return;
		}

		if ( $field['type'] === 'model' ) {
			if ( ! class_exists( \MetaBox\CustomTable\Model\Factory::class ) ) {
				return;
			}
			$model = \MetaBox\CustomTable\Model\Factory::get( $field['model'] ?? '' );
			if ( ! $model ) {
				return;
			}
			$field['name'] = $model->labels['name'] ?? $model->labels['singular_name'] ?? '';
		}
	}
}
