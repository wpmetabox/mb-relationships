(function($, document) {
    'use strict';

    /**
     * Transform select fields into beautiful dropdown with select2 library.
     */
    function transform() {
        var $this = $(this),
            $post_type = $this.data('post-type');

        $this.select2({
            ajax: {
                url: ajaxurl, // AJAX URL is predefined in WordPress admin
                dataType: 'json',
                delay: 250, // delay in ms while typing when to perform a AJAX search
                data: function(params) {
                    return {
                        q: params.term, // search query
                        action: 'mb_relationships_admin_filter', // AJAX action for admin-ajax.php
                        post_type: $post_type
                    };
                },
                processResults: function(data) {
                    var options = [];
                    if (data) {
                        // data is the array of arrays, and each of them contains ID and the Label of the option
                        jQuery.each(data, function(index,
                            text) { // do not forget that "index" is just auto incremented value
                            options.push({
                                id: text[0],
                                text: text[1]
                            });
                        });
                    }
                    return {
                        results: options
                    };
                },
                cache: true
            },
            minimumInputLength: 3 // the minimum of symbols to input before perform a search
        });

    }

    function init() {
        $('.mb_related_filter').each(transform);
    }

    $(document).ready(init);
})(jQuery);