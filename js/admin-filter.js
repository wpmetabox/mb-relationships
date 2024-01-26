( function ( $, document ) {
    'use strict';

    /**
     * Transform select fields into beautiful dropdown with select2 library.
     */
    function transform() {
        var $this = $( this ),
            $mbr_data_filter = $this.data( 'mbr-filter' );

        var options = {};
        // the minimum of symbols to input before perform a search
        options.minimumInputLength = 3;

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
            var options = [];

            if ( data.success === true ) {
                // data is the array of arrays, and each of them contains ID and the Label of the option
                $.each( data.data, function ( index, option ) {
                    options.push( { id: option.value, text: option.label } );
                } );
            }

            return { results: options };
        };

        $this.select2( options );
    }

    function init() {
        $( '.mb_related_filter' ).each( transform );
    }

    $( document ).ready( init );
} )( jQuery );