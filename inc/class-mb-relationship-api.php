<?php
/**
 * Public API helper functions.
 *
 * @package    Meta Box
 * @subpackage MB Relationship
 */

/**
 * The API class.
 */
class MB_Relationship_API {
	/**
	 * Register a relationship type.
	 *
	 * @param array $args Relationship parameters.
	 *
	 * @return MB_Relationship_Type
	 */
	public function register( $args ) {
		return new MB_Relationship_Type( $args );
	}

	/**
	 * Get connected items.
	 *
	 * @param array $args Parameters, including.
	 *                    $relationship Relationship ID. Auto detect if possible.
	 *                    $direction    'from', 'to' or 'any'.
	 *                    $items        Item ID or array of item IDs.
	 *
	 * @return array
	 */
	public function get_connected( $args ) {
		$args = array(
			'relationship' => 'id',
			'direction'    => 'to',
			'items'        => array(),
		);
		return array();
	}
}
