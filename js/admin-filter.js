( function ( $, document ) {
    'use strict';

    /**
     * Transform select fields into beautiful dropdown with select2 library.
     */
    function transform() {
        const $this = $( this ),
            $mbr_data_filter = $this.data( 'mbr-filter' );

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
                filter: $mbr_data_filter.data
            };
        };

        options.ajax.processResults = function ( data ) {
            let results = [];

            if ( data.success === true ) {
                // data is the array of arrays, and each of them contains ID and the Label of the option
                $.each( data.data, function ( index, option ) {
                    results.push( { id: option.value, text: option.label } );
                } );
            }

            return { results: results };
        };

        $this.select2( options );
    }

    function init() {
        $( '.mb_related_filter' ).each( transform );
    }

    $( document ).ready( init );
} )( jQuery );