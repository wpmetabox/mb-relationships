<?php
/**
 * Query for related custom table model rows.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Class MBR_Query_Model
 */
class MBR_Query_Model {
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
	 * No WP hooks — used only via get_connected / each_connected.
	 */
	public function init(): void {
	}

	/**
	 * Query and get list of model rows.
	 *
	 * @param array            $args         Relationship arguments.
	 * @param array            $query_vars   Extra query variables (unused; kept for API parity).
	 * @param MBR_Relationship $relationship Relationship object.
	 *
	 * @return array
	 */
	public function query( array $args, array $query_vars, MBR_Relationship $relationship ): array {
		global $wpdb;

		$this->normalizer->normalize( $args );

		if ( empty( $args['items'] ) ) {
			return [];
		}

		$connected  = 'from' === ( $args['direction'] ?? '' ) ? 'to' : 'from';
		$settings   = $relationship->$connected;
		$model_name = $settings['field']['model'] ?? '';

		if ( ! $model_name || ! class_exists( \MetaBox\CustomTable\Model\Factory::class ) ) {
			return [];
		}

		$model = \MetaBox\CustomTable\Model\Factory::get( $model_name );
		if ( ! $model || ! $model->table ) {
			return [];
		}

		$table              = $model->table;
		$relationship_query = new MBR_Query( $args );
		$clauses            = [
			'fields'  => "`$table`.*",
			'join'    => '',
			'where'   => '1=1',
			'orderby' => '',
			'groupby' => '',
			'order'   => '',
		];
		$clauses            = $relationship_query->alter_clauses( $clauses, "`$table`.ID" );

		$sql = "SELECT {$clauses['fields']} FROM `$table` {$clauses['join']} WHERE {$clauses['where']}";
		if ( ! empty( $clauses['groupby'] ) ) {
			$sql .= " GROUP BY {$clauses['groupby']}";
		}
		if ( ! empty( $clauses['orderby'] ) ) {
			$sql .= " ORDER BY {$clauses['orderby']}";
			if ( ! empty( $clauses['order'] ) ) {
				$sql .= ' ' . $clauses['order'];
			}
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( $sql );

		return $results ?: [];
	}
}
