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

	/**
	 * Relation for multiple relationships.
	 */
	private $relation;

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
		// Single relationship.
		if ( empty( $this->args['relation'] ) ) {
			if ( empty( $this->args['sibling'] ) ) {
				$this->handle_single_relationship_join( $clauses, $id_column, $pass_thru_order );
			} else {
				$this->handle_single_relationship_sibling( $clauses, $id_column );
			}
		}
		// Multiple relationships.
		else {
			$this->handle_multiple_relationships( $clauses, $id_column );
		}

		$clauses['groupby'] = empty( $clauses['groupby'] ) ? $id_column : "{$clauses['groupby']}, $id_column";

		return $clauses;
	}

	/**
	 * Modify query JOIN statement. Do not support querying by multiple relationships.
	 *
	 * @param array  $clauses         Query clauses.
	 * @param string $id_column       Database column for object ID.
	 * @param bool   $pass_thru_order If TRUE use the WP_Query orderby clause.
	 */
	public function handle_single_relationship_join( &$clauses, $id_column, $pass_thru_order ) {
		global $wpdb;

		$join = $this->build_single_relationship_join( $this->args, $clauses, $id_column, $pass_thru_order );
		$clauses['join'] .= " INNER JOIN $wpdb->mb_relationships AS mbr ON $join";
	}

	private function build_single_relationship_join( $relationship, &$clauses, $id_column, $pass_thru_order ) {
		$source = $relationship['direction'];
		$target = 'from' === $source ? 'to' : 'from';
		$items  = implode( ',', array_map( 'absint', $relationship['items'] ) );

		if ( $relationship['reciprocal'] ) {
			$fields             = "mbr.from AS mbr_from, mbr.to AS mbr_to, mbr.ID AS mbr_id, CASE WHEN mbr.to = $id_column THEN mbr.order_from WHEN mbr.from = $id_column THEN mbr.order_to END AS `mbr_order`";
			$clauses['fields'] .= empty( $clauses['fields'] ) ? $fields : " , $fields";

			if ( ! $pass_thru_order ) {

				if ( 't.term_id' === $id_column ) {
					$clauses['orderby'] = 'ORDER BY `mbr_order` ASC, mbr_id';
					$clauses['order']   = 'DESC';
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
	public function handle_single_relationship_sibling( &$clauses, $id_column ) {
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
	 * Modify query join & where statement for multi-relationship.
	 *
	 * @param string $clauses   Query clauses.
	 * @param array  $args   $WP_query args object.
	 */
	public function handle_multiple_relationships( &$clauses, $id_column ) {
		global $wpdb;

		$this->relation = $this->args['relation'];
		unset( $this->args['relation'] );

		$relationships = $this->args;

		$objects       = array();
		$object_ids    = array();

		foreach ( $relationships as $relationship ) {

			$relationship_type     = $relationship['id'];
			$relationship_source   = $relationship['direction'];
			$object_id             = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE post_type='mb-relationship' AND post_name=%s",
					$relationship_type
				)
			);
			$relationship_settings = get_post_meta( $object_id, 'settings' );
			$relationship_settings = array_shift( $relationship_settings );
			$object_type           = $relationship_settings[ $relationship_source ]['object_type'];

			$object = 'post' === $object_type || 'user' === $object_type
				? $this->get_relationship_object_ids( $relationship, $object_type )
				: null;
			if ( $object ) {
				$object_ids[] = $object;
			}

			$object = 'term' === $object_type
				? $this->get_relationship_objects( $relationship )
				: null;
			if ( null !== $object ) {
				$objects[] = $object;
			}
		}
		if ( $object_ids ) {
			$this->alter_where_clause( $clauses, $object_ids );
		}
		if ( $objects ) {
			$this->alter_join_clause( $clauses, $objects );
		}
	}


	public function alter_where_clause( &$clauses, $object_ids ) {
		global $wpdb;
		$merge_object_ids    = array_shift( $object_ids );
		foreach ( $object_ids as $object ) {
			$merge_object_ids = 'OR' === $this->relation
				? array_merge( $merge_object_ids, $object )
				: array_intersect( $merge_object_ids, $object );
		}
		$merge_object_ids  = implode( ',', $merge_object_ids );
		$clauses['where'] .= " AND $wpdb->posts.ID IN($merge_object_ids)";
	}

	public function alter_join_clause( &$clauses, $objects ) {
		global $wpdb;
		$pos                 = strrpos( $clauses['join'], 'AND ((' );
		$clauses['join']     = 0 < $pos
			? substr( $clauses['join'], 0, $pos + 4 )
			: "INNER JOIN $wpdb->mb_relationships AS mbr ON (";
		$clauses['join']    .= implode( " $this->relation ", $objects ) . ')';
	}

	public function get_relationship_object_ids( $relationship, $object_type ) {
		global $wpdb;
		$relationship_type    = $relationship['id'];
		$relationship_item_id = array_shift( $relationship['items'] );
		$relationship_source  = $relationship['direction'];
		$object_ids           = array();
		if ( 'post' === $object_type ) {
			$objects = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT `from`,`to` FROM $wpdb->mb_relationships
				WHERE `type`=%s AND %s=%d",
					$relationship_type,
					$relationship_source,
					$relationship_item_id
				)
			);
			foreach ( $objects as $object ) {
				$object_ids[] = 'from' === $relationship_source ? $object->to : $object->from;
			}
		}
		if ( 'user' === $object_type ) {
			$objects    = $wpdb->get_results(
				$wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author=%d", $relationship_item_id )
			);
			$object_ids = array();
			foreach ( $objects as $object ) {
				$object_ids[] = $object->ID;
			}
		}
		return $object_ids;
	}

	public function get_relationship_objects( $relationship ) {
		global $wpdb;
		$relationship_type    = $relationship['id'];
		$relationship_item_id = array_shift( $relationship['items'] );
		$relationship_source  = $relationship['direction'];

		$objects    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id=%d",
				$relationship_item_id
			)
		);
		$object_ids = array();
		foreach ( $objects as $object ) {
			$object_ids[] = $object->object_id;
		}
		if ( empty( $object_ids ) ) {
			return null;
		}
		$object_ids = implode( ',', $object_ids );
		return "($wpdb->posts.ID IN($object_ids) AND mbr.$relationship_source IN ($relationship_item_id))";
	}
}
