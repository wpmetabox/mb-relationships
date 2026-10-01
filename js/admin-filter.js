( function ( $ ) {
	'use strict';

	/**
	 * Transform select fields into beautiful dropdown with select2 library.
	 */
	function transform() {
		const $this = $( this );

		const options = {
			allowClear: true,
			minimumInputLength: 1,
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				delay: 250,
				cache: true,
				data: params => {
					const data = {
						q: params.term,
						action: 'mbr_admin_filter',
						_ajax_nonce: MBR.nonce,
						object_type: $this.data( 'object_type' ),
						type: $this.data( 'type' ),
					};
					if ( $this.data( 'relationship_id' ) ) {
						data.relationship_id = $this.data( 'relationship_id' );
						data.from_to = $this.data( 'from_to' );
					}
					return data;
				},
				processResults: response => ( { results: response.data } ),
			}
		};

		$this.select2( options );
	}

	$( () => $( '.mb_related_filter' ).each( transform ) );
} )( jQuery );