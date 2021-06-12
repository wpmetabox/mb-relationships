<?php
/**
 * The relationship query class that alters the WordPress query to get the connected items.
 */

class MBR_Query {
	/**
	 * The relationship query variables.
	 *
	 * @var array
	 */
	private $args;

	public function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * Modify the WordPress query to get connected object.
	 *
	 * @param array  $clauses         Query clauses.
	 * @param string $id_column       Database column for object ID.
	 * @param bool   $pass_thru_order If TRUE use the WP_Query orderby clause.
	 *
	 * @return mixed
	 */
	public function alter_clauses( &$clauses, $id_column, $pass_thru_order = false ) {
		$this->handle_query_join( $clauses, $id_column, $pass_thru_order );

		if ( empty( $this->args['relation'] ) && ! empty( $this->args['sibling'] ) ) {
			$this->handle_query_sibling( $clauses, $id_column );
		}

		$clauses['groupby'] = empty( $clauses['groupby'] ) ? $id_column : "{$clauses['groupby']}, $id_column";

		$this->filter_request_statement( $clauses, $this->args);

		return $clauses;
	}

	/**
	 * Modify query JOIN statement. Support querying by multiple relationships.
	 *
	 * @param array  $clauses         Query clauses.
	 * @param string $id_column       Database column for object ID.
	 * @param bool   $pass_thru_order If TRUE use the WP_Query orderby clause.
	 */
	public function handle_query_join( &$clauses, $id_column, $pass_thru_order ) {
		global $wpdb;

		$join_type     = 'AND';
		$relationships = [];

		if ( isset( $this->args['relation'] ) ) {
			$join_type = $this->args['relation'];
			unset( $this->args['relation'] );
			$relationships = $this->args;
		} else {
			$relationships[] = $this->args;
		}

		$joins = [];
		foreach ( $relationships as $relationship ) {
			$joins[] = $this->build_join( $relationship, $clauses, $id_column, $pass_thru_order );
		}
		$joins = implode( " OR ", $joins );

		$clauses['relation'] = $join_type;
		$clauses['join'] .= " INNER JOIN $wpdb->mb_relationships AS mbr ON $joins";
	}

	private function build_join( $relationship, &$clauses, $id_column, $pass_thru_order ) {
		$source = $relationship['direction'];
		$target = 'from' === $source ? 'to' : 'from';
		$items  = implode( ',', array_map( 'absint', $relationship['items'] ) );

		if ( $relationship['reciprocal'] ) {
			$fields             = "mbr.from AS mbr_from, mbr.to AS mbr_to, mbr.ID AS mbr_id, CASE WHEN mbr.to = $id_column THEN mbr.order_from WHEN mbr.from = $id_column THEN mbr.order_to END AS `mbr_order`";
			$clauses['fields'] .= empty( $clauses['fields'] ) ? $fields : " , $fields";
			
			if ( ! $pass_thru_order ) {

				if ( 't.term_id' === $id_column ) {
					$clauses['orderby'] = 'ORDER BY `mbr_order` ASC, mbr_id';
					$clauses['order'] = 'DESC';
				} else {
					$clauses['orderby'] = '`mbr_order` ASC, mbr_id DESC';
				}	
							
			}

			if ( empty( $clauses['groupby'] ) ) {
				$clauses['groupby'] = 'mbr_from, mbr_to';
			}

			return sprintf(
				" (mbr.type = '%s' AND ((mbr.from = $id_column AND mbr.to IN (%s)) OR (mbr.to = $id_column AND mbr.from IN (%s)))) ",
				$relationship['id'],
				$items,
				$items
			);
		}

		if ( ! $pass_thru_order ) {
			$orderby            = "mbr.order_$source";
			$clauses['orderby'] = 't.term_id' === $id_column ? "ORDER BY $orderby" : $orderby;
		}

		$alias              = "mbr_{$relationship['id']}_{$source}";
		$fields             = "mbr.$source AS `$alias`";
		$clauses['fields'] .= empty( $clauses['fields'] ) ? $fields : " , $fields";
		if ( empty( $clauses['groupby'] ) ) {
			$clauses['groupby'] = "`$alias`";
		}

		return sprintf(
			" (mbr.$target = $id_column AND mbr.type = '%s' AND mbr.$source IN (%s)) ",
			$relationship['id'],
			$items
		);
	}

	/**
	 * Modify query to get sibling items. Do not support querying by multiple relationships.
	 *
	 * @param array  $clauses   Query clauses.
	 * @param string $id_column Database column for object ID.
	 */
	public function handle_query_sibling( &$clauses, $id_column ) {
		global $wpdb;

		$source = $this->args['direction'];
		$target = 'from' === $source ? 'to' : 'from';
		$items  = array_map( 'absint', $this->args['items'] );
		$ids    = implode( ',', $items );
		$items  = "(
			SELECT DISTINCT `$target`
			FROM $wpdb->mb_relationships
			WHERE `type` = '{$this->args['id']}'
			AND `$source` IN ($ids)
		)";
		$tmp    = $source;
		$source = $target;
		$target = $tmp;

		$clauses['join'] = " INNER JOIN $wpdb->mb_relationships AS mbr ON mbr.$target = $id_column";

		$where  = sprintf(
			"mbr.type = '%s' AND mbr.$source IN (%s)",
			$this->args['id'],
			$items
		);
		$where .= " AND mbr.$target NOT IN ($ids)";

		$clauses['where'] .= empty( $clauses['where'] ) ? $where : " AND $where";		
	}

	/**
	 * Modify query join statement to replace AND statement.
	 *
	 * @param string $clauses   Query clauses.
	 * @param array  $args   $WP_query args object.
	 */	
	public function filter_request_statement( &$clauses, $args ) {
		global $wpdb;		
		$relationships = $args;
		
		if ( is_array( $relationships ) ) {
			$count_clause = 0;
			foreach ( $relationships as $relationship ) {
				$count_clause += isset( $relationship['id'] );
			}
			if( $count_clause == 1 ) return;
			$relationships_operator = $clauses['relation'];			
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
							if ( ! empty( $this_statement_sql ) ) {
								$join_on_clauses[] = "($wpdb->posts.ID IN(${this_statement_sql}) AND mbr.${direction} IN (${item_id}))";
							}
						}
					}
					
					
				}
			}
			if ($in_post_id){
				$this_statement_sql = implode(",", $in_post_id);
				$this_statement_sql = " AND $wpdb->posts.ID IN(${this_statement_sql})";
				$clauses['where'] .= $this_statement_sql;				
			}
			if ($join_on_clauses){
				$tag_beginning = 'AND ((';
				$pos                = strpos( $clauses['join'], $tag_beginning );
				$clauses['join'] = substr( $clauses['join'], 0, $pos + strlen($tag_beginning) - 2);
				$clauses['join'] .= implode( " $relationships_operator ", $join_on_clauses );
				$clauses['join'] .= ')';
			}
		}
		
	}
}
