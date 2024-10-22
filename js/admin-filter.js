( function ( $, document ) {
	'use strict';

	/**
	 * Transform select fields into beautiful dropdown with select2 library.
	 */
	function transform() {
		const $this = $( this );

		let options = {};
		// the minimum of symbols to input before perform a search
		options.minimumInputLength = 1;

		options.ajax = {
			url: ajaxurl,
			dataType: 'json',
			delay: 250,
			cache: true
		};

		options.ajax.data = function ( params ) {
			return {
				q: params.term,
				action: 'mbr_admin_filter',
				_ajax_nonce: MBR.nonce,
				object_type: $this.data( 'object_type' ),
				type: $this.data( 'type' ),
			};
		};

		options.ajax.processResults = response => ( { results: response.data } );

		$this.select2( options );
	}

	function init() {
		$( '.mb_related_filter' ).each( transform );
	}

	$( document ).ready( init );
} )( jQuery );