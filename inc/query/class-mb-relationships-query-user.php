<?php
/**
 * Query for related users using WP_Query.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Class MB_Relationships_Query_User
 */
class MB_Relationships_Query_User {
	/**
	 * Filter the WordPress query to get connected users.
	 */
	public function init() {
		add_action( 'pre_user_query', array( $this, 'parse_query' ), 20 );
	}

	/**
	 * Parse query variables.
	 * Fires after the main query vars have been parsed.
	 *
	 * @param WP_User_Query $query The current WP_User_Query instance, passed by reference.
	 */
	public function parse_query( WP_User_Query $query ) {
		global $wpdb;

		$args = $query->get( 'relationship' );
		if ( ! $args ) {
			return;
		}

		$relationship_query = new MB_Relationships_Query( $args );

		$clauses = array();
		$map     = array(
			'join'  => 'query_from',
			'where' => 'query_where',
		);
		foreach ( $map as $clause => $key ) {
			$clauses[ $clause ] = $query->$key;
		}
		$clauses = $relationship_query->alter_clauses( $clauses, "$wpdb->users.ID" );

		foreach ( $map as $clause => $key ) {
			$query->$key = $clauses[ $clause ];
		}
	}
}

