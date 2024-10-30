(function($) {

    $( '#bulk_edit' ).live( 'click', function() {
        // define the bulk edit row
        var $bulk_row = $( '#bulk-edit' );
        // get the selected post ids that are being edited
        var $post_ids = new Array();
        $bulk_row.find( '#bulk-titles' ).children().each( function() {
            $post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
        });
        // get the release date
        var $translated = $bulk_row.find( 'select[name="translated"]' ).val();

        if ( $translated != '-1' ) {
            // save the data
            $.ajax({
                url: ajaxurl, // this is a variable that WordPress has already defined for us
                type: 'POST',
                async: false,
                cache: false,
                data: {
                    action: 'magic_post_translate_save_bulk_edit', // this is the name of our WP AJAX function that we'll set up next
                    post_ids: $post_ids, // and these are the 2 parameters we're passing to our function
                    translated: $translated
                }
            });
        }
    });

})(jQuery);