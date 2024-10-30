function sendposts( posts, a, count, nonce ) {
    setTimeout(function () {
        // Send data to WordPress admin-ajax file
        jQuery.ajax({
            url : translationJsVars.wp_ajax_url,
            method : 'POST',
            data : {
                action        : 'get_users_table_data',
                ids           : posts,
                a             : a,
                count         : count,
                nonce         : nonce
            },
            success : function( data ) {
                if ( data.success ) {

                    jQuery("#results").append( data.data.msg+'<br/>' );
                    var percent = data.data.percent;
                    jQuery( "#progressbar" ).progressbar({
                        value: percent
                    });
                    if( percent == 100 ) {
                        jQuery("#results").append("<br/><span class=\"successful\">"+translationJsVars.translations.successful+"</span>");
                    }


                    jQuery( "#results" ).append( data );
                    document.getElementById( "results" ).scrollTop = 100000;

                    a++;
                    if ( a <= count) {
                        sendposts( posts, a, count, nonce );
                    }
                } else {
                    jQuery("#results").append( translationJsVars.translations.error_generation );
                }
            },
            error : function( data ) {
                jQuery("#results").append( translationJsVars.translations.error_translate ); 
            }
        });

    }, 1);
}

jQuery(function() {
    jQuery( "#progressbar" ).progressbar({
        value: 0
    });
});

jQuery(function() {
    jQuery("#hide-before-import").css("display", "block");
    jQuery( "#progressbar" ).progressbar({
        value: 1
    });
    return false;
});