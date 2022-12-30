<?php
/**
 * FacetWP integration
 */
class MB_Relationships_FacetWP extends FacetWP_Facet {

	const FACET_TYPE = 'mb_relationships';

	/**
	 * FacetWP Indexer.
	 *
	 * @var FacetWP_Indexer
	 */
	protected $indexer;

	/**
	 * Construct the class.
	 *
	 * @since 1.12.0
	 */
	public function __construct() {
		$this->label = __( 'MB Relationships', 'mb-relationships' );

		// Add all registered relationships as FacetWP sources.
		add_filter( 'facetwp_facet_sources', [ $this, 'facet_sources' ] );

		// Hook into indexer.
		add_filter( 'facetwp_indexer_post_facet', [ $this, 'facetwp_indexer_post_facet' ], 10, 2 );
	}

	/**
	 * Add all registerd relationships as facet sources.
	 *
	 * @since 1.12.0
	 *
	 * @param array $sources FacetWP sources.
	 *
	 * @return array
	 */
	public function facet_sources( $sources ) {
		$choices = [];

		$relationships = MB_Relationships_API::get_all_relationships();

		foreach ( $relationships as $relationship ) {
			$choices[ self::FACET_TYPE . '/' . $relationship->id ] = $relationship->id;
		}

		if ( ! empty( $choices ) ) {
			$sources[ self::FACET_TYPE ] = array(
				'label'   => __( 'MB Relationships', 'mb-relationships' ),
				'choices' => $choices,
				'weight'  => 7,
			);
		}

		return $sources;
	}

	/**
	 * Index MB relationships.
	 *
	 * @since 1.12.0
	 *
	 * @param bool  $bypass Bypass default indexing.
	 * @param array $params Extra helper data.
	 *
	 * @return array
	 */
	public function facetwp_indexer_post_facet( $bypass, $params ) {
		if ( ! isset( $params['facet']['source'] ) || self::FACET_TYPE . '/' !== substr( $params['facet']['source'], 0, 17 ) ) {
			return $bypass;
		}

		$this->indexer   = FWP()->indexer;
		$relationship_id = substr( $params['facet']['source'], 17 );

		$connected_objects = MB_Relationships_API::get_connected([
			'id' => $relationship_id,
			'from' => $params['defaults']['post_id'],
		]);

		// If no related objects, stop processing.
		if ( empty( $connected_objects ) ) {
			return true;
		}

		foreach ( $connected_objects as $connected_object ) {
			$this->index_field_value( $connected_object, $params['defaults'] );
		}

		return $bypass;
	}

	/**
	 * Manually index a relationship value.
	 *
	 * @since 1.12.0
	 *
	 * @param WP_Post|WP_Term|WP_User $object Connected object.
	 * @param array                   $params Extra helper data.
	 *
	 * @return void
	 */
	protected function index_field_value( $object, $params ) {
		switch ( get_class( $object ) ) {
			case WP_Term::class:
				$params['facet_value']         = $object->term_id;
				$params['facet_display_value'] = $object->name;
				break;

			case WP_User::class:
				$params['facet_value']         = $object->ID;
				$params['facet_display_value'] = $object->display_name;
				break;

			case WP_Post::class:
			default:
				$params['facet_value']         = $object->ID;
				$params['facet_display_value'] = get_the_title( $object );
				break;
		}

		/**
		 * Filters the FacetWP data for a connected object.
		 *
		 * @since 1.12.0
		 *
		 * @param array $params FacetWP object params.
		 * @param mixed $object Connected object.
		 *
		 * @return array
		 */
		$params = apply_filters( 'mb-relationships-facet-index-value', $params, $object );

		$this->indexer->index_row( $params );
	}
}
