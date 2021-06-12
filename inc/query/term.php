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
class MBR_Query_Term {
	/**
	 * Query normalizer.
	 *
	 * @var MBR_Query_Normalizer
	 */
	protected $normalizer;

	/**
	 * Constructor
	 *
	 * @param MBR_Query_Normalizer $normalizer Query normalizer.
	 */
	public function __construct( MBR_Query_Normalizer $normalizer ) {
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
		$query = new MBR_Query( $args );

		return $query->alter_clauses( $clauses, 't.term_id' );
	}

	/**
	 * Query and get list of items.
	 *
	 * @param array            $args         Relationship arguments.
	 * @param array            $query_vars   Extra query variables.
	 * @param MBR_Relationship $relationship Relationship object.
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

	/**
	 * Modify query join statement to replace AND statement.
	 *
	 * @param string $sql   the request sql statement.
	 * @param array  $args   $WP_query args object.
	 */
	public function filter_request_statement_2( $sql, $args ) {
		$count_relationsip = 0;
		
		$relationships = $args->get( 'relationship' );		
		if(!is_array($relationships)) return $sql;
		foreach ( $relationships as $relationship ) {
			$count_relationsip += isset( $relationship['id'] ) && isset( $relationship['direction'] );
		}	
		if( $count_relationsip > 1) echo $sql;
		return $sql;
	}
	public function filter_request_statement( $sql, $args ) {
		global $wpdb;		
		$relationships = $args->get( 'relationship' );
		
		if ( is_array( $relationships ) && isset( $relationships['relation'] ) ) {
			$count_clause = 0;
			foreach ( $relationships as $relationship ) {
				$count_clause += isset( $relationship['id'] ) && isset( $relationship['direction'] );
			}
			if( $count_clause == 1){
				return $sql;
			}
			$relationships_operator = $relationships['relation'];
			$sql                    = preg_replace( '/\s+/', ' ', $sql );
			$join_on_clauses        = array();
			$in_post_id				= array();
			
			foreach ( $relationships as $relationship ) {
				if ( isset( $relationship['id'] ) && isset( $relationship['direction'] ) ) {
					$type              = $relationship['id'];
					$item_id           = array_shift( $relationship['items'] );
					$direction         = $relationship['direction'];
					$item_id_direction = $direction === 'from' ? 'to' : 'from';
					if ( 'ID' === $relationship['id_field'] ) {
						$relationship_args    = array(
							'relationship' => array(
								array(
									'id' => $type,
                    				$direction => $item_id,
								),
								'relation' => 'AND'
							),
						);
						$relationship_query   = new WP_Query( $relationship_args );
						$post_id = array();
						while ( $relationship_query->have_posts() ) {
							$relationship_query->the_post();
							$post_id[] = get_the_ID();
						}
						if(empty($in_post_id) || 'OR' === $relationships_operator){
							$in_post_id = array_merge($in_post_id, $post_id);
						}else{
							$in_post_id = array_intersect($in_post_id, $post_id);
						}
						
					} elseif ( 'term_id' === $relationship['id_field'] ) {
						$taxonomies = get_taxonomies();
						$this_tax   = null;
						foreach ( $taxonomies as $tax_type_key => $taxonomy ) {
							if ( $term_object = get_term_by( 'term_id', $item_id, $taxonomy ) ) {
								$this_tax = $taxonomy;
							}
						}
						if ( null != $this_tax ) {
							$args    = array(
								'tax_query' => array(
									array(
										'taxonomy' => $this_tax,
										'field'    => 'term_id',
										'terms'    => $item_id,
									),
								),
							);
							$query   = new WP_Query( $args );
							$post_id = array();
							while ( $query->have_posts() ) {
								$query->the_post();
								$post_id[] = get_the_ID();
							}
							$this_statement_sql = implode( ',', $post_id );
						}
					}
					if ( isset( $this_statement_sql ) && ! empty( $this_statement_sql ) ) {
						$join_on_clauses[] = "($wpdb->posts.ID IN(${this_statement_sql}) AND mbr.${direction} IN (${item_id}))";
					}
				}
			}
			if ($in_post_id){
				$this_statement_sql = implode(",", $in_post_id);
				$this_statement_sql = " AND $wpdb->posts.ID IN(${this_statement_sql})";
				$pos                = strpos( $sql, ' GROUP BY ' );
				$sql                = substr_replace( $sql, $this_statement_sql, $pos, 0 );
			}
			$sql = preg_replace(
				'/' . preg_quote( 'AND ((' ) . '[\s\S]+?' . preg_quote( ')) WHERE' ) . '/',
				'AND (' . implode( " $relationships_operator ", $join_on_clauses ) . ')) WHERE',
				$sql
			);
			$sql = str_replace( ') AND (', ') OR (', $sql );
		}
		echo $sql;
		return $sql;
	}
}
