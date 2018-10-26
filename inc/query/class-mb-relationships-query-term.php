<?php
/**
 * Query for related terms using get_terms().
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Term query class.
 */
class MB_Relationships_Query_Term {
	/**
	 * Query normalizer.
	 *
	 * @var MB_Relationships_Query_Normalizer
	 */
	protected $normalizer;

	/**
	 * Constructor
	 *
	 * @param MB_Relationships_Query_Normalizer $normalizer Query normalizer.
	 */
	public function __construct( MB_Relationships_Query_Normalizer $normalizer ) {
		$this->normalizer = $normalizer;
	}

	/**
	 * Filter the WordPress query to get connected terms.
	 */
	public function init() {
		add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 20, 3 );
	}

	/**
	 * Filters all query clauses at once, for convenience.
	 *
	 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
	 * fields (SELECT), and LIMITS clauses.
	 *
	 * @param array $clauses    Terms query SQL clauses.
	 * @param array $taxonomies An array of taxonomies.
	 * @param array $args       An array of terms query arguments.
	 *
	 * @return array
	 */
	public function terms_clauses( $clauses, $taxonomies, $args ) {
		if ( ! isset( $args['relationship'] ) ) {
			return $clauses;
		}
		$args = $args['relationship'];
		$this->normalizer->normalize( $args );
		$query = new MB_Relationships_Query( $args );

		return $query->alter_clauses( $clauses, 't.term_id' );
	}

	/**
	 * Query and get list of items.
	 *
	 * @param array                         $args         Relationship arguments.
	 * @param array                         $query_vars   Extra query variables.
	 * @param MB_Relationships_Relationship $relationship Relationship object.
	 *
	 * @return array
	 */
	public function query( $args, $query_vars, $relationship ) {
		$query_vars = wp_parse_args(
			$query_vars,
			array(
				'relationship' => $args,
			)
		);
		$connected  = isset( $args['from'] ) ? 'to' : 'from';
		$settings   = $relationship->$connected;
		$query_vars = wp_parse_args(
			$query_vars,
			array(
				'taxonomy'   => $settings['taxonomy'],
				'hide_empty' => false,
			)
		);
		return get_terms( $query_vars );
	}
}
