<?php
/**
 * The model object adapter for custom table models (MB Custom Table).
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * The model object.
 */
class MBR_Model implements MBR_Object_Interface {
	/**
	 * Model name (slug).
	 *
	 * @var string
	 */
	private $model_name;

	/**
	 * Item title template / column.
	 *
	 * @var string
	 */
	private $item_title;

	/**
	 * Label cache for the current request, keyed by model|item_title|id.
	 *
	 * @var array<string, string>
	 */
	private static $label_cache = [];

	/**
	 * Constructor.
	 *
	 * @param string $model_name Model name.
	 * @param string $item_title Optional item title template.
	 */
	public function __construct( $model_name = '', $item_title = '' ) {
		$this->model_name = (string) $model_name;
		$this->item_title = (string) $item_title;
	}

	/**
	 * Query model items via ModelField (shared by admin UI / labels).
	 *
	 * @param int|null $id         Specific ID, or null to search.
	 * @param string   $model      Model name.
	 * @param string   $item_title Item title template.
	 * @param array    $query_args Extra query args (e.g. s, limit).
	 *
	 * @return array
	 */
	public static function query_items( $id, string $model, string $item_title = '', array $query_args = [] ): array {
		if ( ! $model || ! class_exists( \MetaBox\CustomTable\ModelField::class ) ) {
			return [];
		}

		return \MetaBox\CustomTable\ModelField::query( $id, [
			'id'           => '',
			'type'         => 'model',
			'model'        => $model,
			'item_title'   => $item_title,
			'ajax'         => true,
			'query_args'   => $query_args,
			'clone'        => false,
			'_original_id' => '',
		] );
	}

	/**
	 * Get current object ID in admin.
	 *
	 * @return int|false
	 */
	public function get_current_admin_id() {
		$id = filter_input( INPUT_GET, 'model-id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id ) {
			$id = filter_input( INPUT_POST, 'model-id', FILTER_SANITIZE_NUMBER_INT );
		}
		return is_numeric( $id ) ? absint( $id ) : false;
	}

	/**
	 * Get current object ID on the frontend.
	 *
	 * Models have no frontend singular context; do not read model-id from the request.
	 *
	 * @return int|false
	 */
	public function get_current_id() {
		return false;
	}

	/**
	 * Get HTML link to the object.
	 *
	 * @param int $id Object ID.
	 *
	 * @return string
	 */
	public function get_link( $id ): string {
		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->get_edit_url( $id ) ),
			esc_html( $this->get_label( $id ) )
		);
	}

	/**
	 * Render HTML of the object to show in the frontend.
	 *
	 * @param mixed $item Model row (object/array) or ID.
	 * @param array $atts Shortcode attributes.
	 */
	public function render( $item, $atts ): string {
		// Models have no public permalink; never expose admin edit URLs on the frontend.
		return esc_html( $this->get_label( $this->get_item_id( $item ) ) );
	}

	/**
	 * Render HTML of the object on the back end (admin column).
	 *
	 * @param mixed $item   Model row (object/array) or ID.
	 * @param array $config Admin column config.
	 */
	public function render_admin( $item, $config ): string {
		$id   = $this->get_item_id( $item );
		$text = $this->get_label( $id );

		if ( false === ( $config['link'] ?? 'view' ) ) {
			return esc_html( $text );
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->get_edit_url( $id ) ),
			esc_html( $text )
		);
	}

	/**
	 * Get database ID field.
	 *
	 * @return string
	 */
	public function get_db_field(): string {
		return 'ID';
	}

	/**
	 * Resolve item ID from a row object/array or raw ID.
	 *
	 * @param mixed $item Model row or ID.
	 */
	private function get_item_id( $item ): int {
		if ( is_object( $item ) ) {
			return (int) ( $item->ID ?? 0 );
		}
		if ( is_array( $item ) ) {
			return (int) ( $item['ID'] ?? 0 );
		}
		return (int) $item;
	}

	private function get_edit_url( int $id ): string {
		return admin_url( sprintf(
			'admin.php?page=model-%s&model-action=edit&model-id=%d',
			rawurlencode( $this->model_name ),
			$id
		) );
	}

	/**
	 * Get display label for a model row.
	 *
	 * @param int $id Object ID.
	 */
	public function get_label( int $id ): string {
		if ( ! $id || ! $this->model_name ) {
			return '#' . $id;
		}

		$cache_key = $this->model_name . '|' . $this->item_title . '|' . $id;
		if ( isset( self::$label_cache[ $cache_key ] ) ) {
			return self::$label_cache[ $cache_key ];
		}

		$items = self::query_items( $id, $this->model_name, $this->item_title );
		$label = $items[ $id ]['label'] ?? ( '#' . $id );

		self::$label_cache[ $cache_key ] = $label;

		return $label;
	}
}
