<?php
/**
 * REST API to manage relationships via JSON API
 */
class MB_Relationships_REST_API {

	/**
	 * The namespace of this controller’s route.
	 *
	 * @var string
	 */
	const NAMESPACE = 'mb-relationships/v1';

	/**
	 * All registered relationships.
	 *
	 * @var array<int, string>
	 */
	private $relationships = [];

	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/(?P<relationship>[a-zA-Z0-9-_]+)/exists',
			[
				'methods'             => WP_REST_Server::READABLE,
				'args'                => $this->relationship_args(),
				'permission_callback' => [ $this, 'read_relationship_permission' ],
				'callback'            => [ $this, 'has_relationship' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/(?P<relationship>[a-zA-Z0-9-_]+)/connected-from/(?P<from>[\d]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'args'                => $this->connected_from_relationship_args(),
				'permission_callback' => [ $this, 'read_relationship_permission' ],
				'callback'            => [ $this, 'connected_from_relationship' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/(?P<relationship>[a-zA-Z0-9-_]+)/connected-to/(?P<to>[\d]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'args'                => $this->connected_to_relationship_args(),
				'permission_callback' => [ $this, 'read_relationship_permission' ],
				'callback'            => [ $this, 'connected_to_relationship' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/(?P<relationship>[a-zA-Z0-9-_]+)/',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->create_relationship_args(),
				'permission_callback' => [ $this, 'create_relationship_permission' ],
				'callback'            => [ $this, 'create_relationship' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/(?P<relationship>[a-zA-Z0-9-_]+)/',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'args'                => $this->relationship_args(),
				'permission_callback' => [ $this, 'delete_relationship_permission' ],
				'callback'            => [ $this, 'delete_relationship' ],
			]
		);
	}

	/**
	 * API arguments.
	 */
	public function relationship_args() : array {
		return [
			'relationship' => [
				'description'       => esc_html__( 'The ID of the relationship', 'mb-relationships' ),
				'required'          => true,
				'type'              => 'string',
				'enum'              => $this->all_relationships(),
				'validate_callback' => [ $this, 'validate_relationship_id' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'from'         => [
				'description'       => esc_html__( 'The ID of “from” resource', 'mb-relationships' ),
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => [ $this, 'validate_integer' ],
				'sanitize_callback' => 'absint',
			],
			'to'           => [
				'description'       => esc_html__( 'The ID of “to” resource', 'mb-relationships' ),
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => [ $this, 'validate_integer' ],
				'sanitize_callback' => 'absint',
			],
		];
	}

	/**
	 * Arguments for the connected from API endpoint.
	 */
	public function connected_from_relationship_args() : array {
		$arguments = $this->relationship_args();

		unset( $arguments['to'] );

		return $arguments;
	}

	/**
	 * Arguments for the connected to API endpoint.
	 */
	public function connected_to_relationship_args() : array {
		$arguments = $this->relationship_args();

		unset( $arguments['from'] );

		return $arguments;
	}

	/**
	 * Additional arguments for the create API endpoint.
	 */
	public function create_relationship_args() : array {
		return array_merge(
			$this->relationship_args(),
			[
				'order_from' => [
					'description'       => esc_html__( 'The order of the “from” resource; defaults to “1”', 'mb-relationships' ),
					'type'              => 'integer',
					'validate_callback' => [ $this, 'validate_integer' ],
				],
				'order_to'   => [
					'description'       => esc_html__( 'The order of the “to” resource; defaults to “1”', 'mb-relationships' ),
					'type'              => 'integer',
					'validate_callback' => [ $this, 'validate_integer' ],
				],
			]
		);
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the 'filter' argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function validate_integer( $value, $request, $param ) {
		if ( ! absint( $value ) > 0 ) {
			// Translators: % is the key.
			return new WP_Error( 'rest_invalid_param', sprintf( __( 'The % argument must be a positive integer.', 'mb-relationships' ), $param ), [ 'status' => 400 ] );
		}

		return true;
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the 'filter' argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function validate_relationship_id( $value, $request, $param ) {
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'The relationship argument must be a string.', 'mb-relationships' ), [ 'status' => 400 ] );
		}

		if ( ! in_array( $value, $this->all_relationships(), true ) ) {
			// Translators: %1$s is the value; %2$s is a list of available relationship IDs.
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not one of %2$s', 'mb-relationships' ), $param, implode( ', ', $this->all_relationships() ) ), [ 'status' => 400 ] );
		}

		return true;
	}

	/**
	 * Determine whether the current user has permission to use the has_relationship endpoint.
	 *
	 * @return WP_Error|bool
	 */
	public function read_relationship_permission() {

		/**
		 * Whether the REST API allows unauthenticated users to read relationships.
		 *
		 * @param bool $allow_public_rest_api_read Whether the REST API allows unauthenticated users to read relationships.
		 *
		 * @return bool
		 */
		$allow_public_access = apply_filters( 'mb_relationships_rest_api_can_read_relationships_public', true );

		if ( $allow_public_access ) {
			return true;
		}

		if ( 0 === get_current_user_id() ) {
			return new WP_Error( 'rest-forbidden', __( 'You are not allowed to access this API endpoint.', 'mb-relationships' ), [ 'status' => 401 ] );
		}

		/**
		 * Whether the REST API allows authenticated users to read relationships.
		 *
		 * @param bool $allow_authenticated_user_read Whether the REST API allows authenticated users to read relationships.
		 */
		$permission = apply_filters( 'mb_relationships_rest_api_can_read_relationships', 'read' );

		if ( ! current_user_can( $permission ) ) {
			return new WP_Error( 'rest-forbidden', __( 'You are not allowed to access this API endpoint.', 'mb-relationships' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * Determine whether the current user has permission to use the create_relationship endpoint.
	 *
	 * @return WP_Error|bool
	 */
	public function create_relationship_permission() {

		/**
		 * Whether the REST API allows unauthenticated users to create relationships.
		 *
		 * @param bool $allow_public_rest_api_create Whether the REST API allows unauthenticated users to create relationships.
		 *
		 * @return bool
		 */
		$allow_public_access = apply_filters( 'mb_relationships_rest_api_can_create_relationships_public', false );

		if ( $allow_public_access ) {
			return true;
		}

		if ( 0 === get_current_user_id() ) {
			return new WP_Error( 'rest-forbidden', __( 'You are not allowed to access this API endpoint.', 'mb-relationships' ), [ 'status' => 401 ] );
		}

		/**
		 * Whether the REST API allows authenticated users to create relationships.
		 *
		 * @param bool $allow_authenticated_user_create Whether the REST API allows authenticated users to create relationships.
		 */
		$permission = apply_filters( 'mb_relationships_rest_api_can_create_relationships', 'publish_posts' );

		if ( current_user_can( $permission ) ) {
			return true;
		}

		return new WP_Error( 'rest-forbidden', __( 'You are not allowed to access this API endpoint.', 'mb-relationships' ), [ 'status' => 403 ] );
	}

	/**
	 * Determine whether the current user has permission to use the delete_relationship endpoint.
	 *
	 * @return WP_Error|bool
	 */
	public function delete_relationship_permission() {

		/**
		 * Whether the REST API allows unauthenticated users to delete relationships.
		 *
		 * @param bool $allow_public_rest_api_delete Whether the REST API allows unauthenticated users to delete relationships.
		 *
		 * @return bool
		 */
		$allow_public_access = apply_filters( 'mb_relationships_rest_api_can_delete_relationships_public', false );

		if ( $allow_public_access ) {
			return true;
		}

		if ( 0 === get_current_user_id() ) {
			return new WP_Error( 'rest-forbidden', __( 'You are not allowed to access this API endpoint.', 'mb-relationships' ), [ 'status' => 401 ] );
		}

		/**
		 * Whether the REST API allows authenticated users to delete relationships.
		 *
		 * @param bool $allow_authenticated_user_create Whether the REST API allows authenticated users to create relationships.
		 */
		$permission = apply_filters( 'mb_relationships_rest_api_can_delete_relationships', 'delete_posts' );

		if ( current_user_can( $permission ) ) {
			return true;
		}

		return new WP_Error( 'rest-forbidden', __( 'You are not allowed to access this API endpoint.', 'mb-relationships' ), [ 'status' => 403 ] );
	}

	/**
	 * Checks if the given from and to have a relationship for the given relationship ID.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success or WP_Error object on failure.
	 */
	public function has_relationship( $request ) {
		$relationship = $this->get_url_field_from_request( $request, 'relationship' );
		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$to = $this->get_query_field_from_request( $request, 'to' );
		if ( is_wp_error( $to ) ) {
			return $to;
		}

		$from = $this->get_query_field_from_request( $request, 'from' );
		if ( is_wp_error( $from ) ) {
			return $from;
		}

		return $this->generic_response( $from, $to, $relationship );
	}

	/**
	 * Returns objects connected to the specified from object.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success or WP_Error object on failure.
	 */
	public function connected_from_relationship( $request ) {
		$relationship = $this->get_url_field_from_request( $request, 'relationship' );
		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$from = $this->get_url_field_from_request( $request, 'from' );
		if ( is_wp_error( $from ) ) {
			return $from;
		}

		$relationship_object = MB_Relationships_API::get_relationship( $relationship );

		switch ( $relationship_object->to['object_type'] ) {
			case 'term':
				$field = 'term_id';
				break;

			case 'post':
			case 'user':
			default:
				$field = 'ID';
				break;
		}

		$objects = MB_Relationships_API::get_connected(
			[
				'id'   => $relationship,
				'from' => $from,
			]
		);

		return rest_ensure_response(
			[
				'relationship' => $relationship,
				'from'         => $from,
				'to'           => wp_list_pluck( $objects, $field ),
			]
		);
	}

	/**
	 * Returns objects connected to the specified from object.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success or WP_Error object on failure.
	 */
	public function connected_to_relationship( $request ) {
		$relationship = $this->get_url_field_from_request( $request, 'relationship' );
		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$to = $this->get_url_field_from_request( $request, 'to' );
		if ( is_wp_error( $to ) ) {
			return $to;
		}

		$relationship_object = MB_Relationships_API::get_relationship( $relationship );

		switch ( $relationship_object->from['object_type'] ) {
			case 'term':
				$field = 'term_id';
				break;

			case 'post':
			case 'user':
			default:
				$field = 'ID';
				break;
		}

		$objects = MB_Relationships_API::get_connected(
			[
				'id' => $relationship,
				'to' => $to,
			]
		);

		return rest_ensure_response(
			[
				'relationship' => $relationship,
				'from'         => wp_list_pluck( $objects, $field ),
				'to'           => $to,
			]
		);
	}

	/**
	 * Creates a relationship.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success or WP_Error object on failure.
	 */
	public function create_relationship( $request ) {
		$relationship = $this->get_url_field_from_request( $request, 'relationship' );
		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$to = $this->get_body_field_from_request( $request, 'to' );
		if ( is_wp_error( $to ) ) {
			return $to;
		}

		$from = $this->get_body_field_from_request( $request, 'from' );
		if ( is_wp_error( $from ) ) {
			return $from;
		}

		$order_to = $this->get_body_field_from_request( $request, 'order_to' );
		if ( is_wp_error( $order_to ) ) {
			$order_to = 1;
		}

		$order_from = $this->get_body_field_from_request( $request, 'order_from' );
		if ( is_wp_error( $order_from ) ) {
			$order_from = 1;
		}

		$added = MB_Relationships_API::add( $from, $to, $relationship, $order_from, $order_to );

		return $this->generic_response( $from, $to, $relationship );
	}

	/**
	 * Deletes a relationship.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success or WP_Error object on failure.
	 */
	public function delete_relationship( $request ) {
		$relationship = $this->get_url_field_from_request( $request, 'relationship' );
		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$to = $this->get_query_field_from_request( $request, 'to' );
		if ( is_wp_error( $to ) ) {
			return $to;
		}

		$from = $this->get_query_field_from_request( $request, 'from' );
		if ( is_wp_error( $from ) ) {
			return $from;
		}

		$deleted = MB_Relationships_API::delete( $from, $to, $relationship );

		return $this->generic_response( $from, $to, $relationship );
	}

	/**
	 * Generic API response.
	 *
	 * @param int    $from         “From” ID.
	 * @param int    $to           “To” ID.
	 * @param string $relationship Relationship ID.
	 *
	 * @return WP_REST_Response Response object.
	 */
	private function generic_response( $from, $to, $relationship ) {
		return rest_ensure_response(
			[
				'has_relationship' => MB_Relationships_API::has( $from, $to, $relationship ),
				'relationship'     => $relationship,
				'from'             => $from,
				'to'               => $to,
			]
		);
	}

	/**
	 * Retrieve field from request URL parameters.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param int|string      $key     Parameter key.
	 *
	 * @return mixed|WP_Error Request value on success or WP_Error object on failure.
	 */
	private function get_url_field_from_request( WP_REST_Request $request, $key ) {
		if ( ! array_key_exists( $key, $request->get_url_params() ) ) {
			// Translators: %s is the key to retrieve.
			return new WP_Error( 'missing-field', sprintf( __( 'Missing %s ID.', 'mb-relationships' ), $key ) );
		}

		return $request->get_url_params()[ $key ];
	}

	/**
	 * Retrieve field from request query parameters.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param int|string      $key     Parameter key.
	 *
	 * @return mixed|WP_Error Request value on success or WP_Error object on failure.
	 */
	private function get_query_field_from_request( WP_REST_Request $request, $key ) {
		if ( ! array_key_exists( $key, $request->get_query_params() ) ) {
			// Translators: %s is the key to retrieve.
			return new WP_Error( 'missing-field', sprintf( __( 'Missing %s ID.', 'mb-relationships' ), $key ) );
		}

		return $request->get_query_params()[ $key ];
	}

	/**
	 * Retrieve field from request body parameters.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param int|string      $key     Parameter key.
	 *
	 * @return mixed|WP_Error Request value on success or WP_Error object on failure.
	 */
	private function get_body_field_from_request( WP_REST_Request $request, $key ) {
		if ( ! array_key_exists( $key, $request->get_body_params() ) ) {
			// Translators: %s is the key to retrieve.
			return new WP_Error( 'missing-field', sprintf( __( 'Missing %s ID.', 'mb-relationships' ), $key ) );
		}

		return $request->get_body_params()[ $key ];
	}

	/**
	 * Get all registered relationships.
	 *
	 * @return array<int, string>
	 */
	private function all_relationships() {
		if ( empty( $this->relationships ) ) {
			$this->relationships = array_values( array_keys( MB_Relationships_API::get_all_relationships() ) );
		}

		return $this->relationships;
	}
}
