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
	function filter_request_statement( $sql, $args ){	
		global $wpdb;
		$prefix = $wpdb->prefix;			
		$relationships = $args->get( 'relationship' );		
		
		if( is_array($relationships) && isset($relationships['relation']) && $relationships['relation'] === 'AND' ) // No global $wp_query here
		{	
			$sql = preg_replace('/\s+/', ' ', $sql);
			$sql_statement = null;
			$not_first_statement = false;

			foreach($relationships as $relationship){
				if( $not_first_statement && isset($relationship['id']) && isset($relationship['direction']) ){					
					$type = $relationship['id'];
					$post_id = implode(",", $relationship['items']);
					$direction = $relationship['direction'];
					$post_id_direction = $direction === 'from' ? 'to' : 'from';					
					
					$remove_statements = ["AND (mbr.${post_id_direction} = ${prefix}posts.ID AND mbr.type = '${type}' AND mbr.${direction} IN (${post_id}))"];
					
					$sql = str_replace($remove_statements, null, $sql);					
					$sql_statement = $sql_statement == null ? $sql : $sql_statement;
					$tagOne = " ON ";
					$tagTwo = " WHERE ";					
					$replacement = "(mbr.${post_id_direction} = ${prefix}posts.ID AND mbr.type = '${type}' AND mbr.${direction} IN (${post_id}))";					
								
					$startTagPos = strrpos($sql_statement, $tagOne) + 4;
					$endTagPos = strrpos($sql_statement, $tagTwo);
					$tagLength = $endTagPos - $startTagPos;	
					
					$this_statement_sql = substr_replace($sql_statement, $replacement, $startTagPos, $tagLength);
					$this_statement_sql = str_replace(["SQL_CALC_FOUND_ROWS ", ".*"], [null, ".ID"], $this_statement_sql);					
					$this_statement_sql = preg_replace('/posts.ID[\s\S]+? FROM/', 'posts.ID FROM', $this_statement_sql);
					$this_statement_sql = substr($this_statement_sql, 0, strpos($this_statement_sql, " GROUP BY"));

					$this_statement_sql = " AND ${prefix}posts.ID IN(${this_statement_sql})";
					$pos = strpos($sql, " GROUP BY ");
					$sql = substr_replace($sql, $this_statement_sql, $pos, 0);					
				}
				$not_first_statement = isset($relationship['id']) && isset($relationship['direction']);				
			}						   
		}		
		return $sql;
	}	
}
