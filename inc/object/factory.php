<?php
/**
 * The simple object factory.
 *
 * @package    Meta Box
 * @subpackage MB Relationships
 */

/**
 * Object factory class.
 */
class MBR_Object_Factory {
	/**
	 * For storing instances.
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Get object based on type.
	 *
	 * @param string $type Object type.
	 * @param array  $args Optional side settings (for model: field.model, field.item_title).
	 *
	 * @return MBR_Object_Interface
	 */
	public function build( $type, array $args = [] ) {
		if ( 'model' === $type ) {
			$model      = $args['field']['model'] ?? ( $args['model'] ?? '' );
			$item_title = $args['field']['item_title'] ?? '';
			$key        = 'model:' . $model . ':' . $item_title;

			if ( ! isset( $this->data[ $key ] ) ) {
				$this->data[ $key ] = new MBR_Model( $model, $item_title );
			}

			return $this->data[ $key ];
		}

		if ( isset( $this->data[ $type ] ) ) {
			return $this->data[ $type ];
		}

		$class               = 'MBR_' . ucfirst( $type );
		$this->data[ $type ] = new $class();

		return $this->data[ $type ];
	}
}
