<?php
/**
 * FacetWP integration
 */
class MB_Relationships_FacetWP extends FacetWP_Facet {

	const FACET_TYPE = 'mb_relationships';

	/**
	 * Construct the class.
	 *
	 * @since 1.12.0
	 */
	public function __construct() {
		$this->label = __( 'MB Relationships', 'mb-relationships' );
		add_filter( 'facetwp_facet_sources', [ $this, 'facet_sources' ] );
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
}
