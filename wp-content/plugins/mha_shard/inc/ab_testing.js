jQuery(function ($) {

    let ab_testing_data = 'ab=activated',
        ab_testing_get = 'get=activated';

    if($('#ab-testing-data').length){
        let ab_testing_data_json = $('#ab-testing-data').html(),
            ab_array = JSON.parse( ab_testing_data_json );

        ab_testing_data += '&current_id='+ab_array.current_id;
        ab_testing_data += '&current_url='+ab_array.current_url;
        ab_testing_data += '&current_path='+ab_array.current_path;
        ab_testing_data += '&current_referrer='+ab_array.current_referrer;
    }

    if($('#ab-testing-get').length){
        let ab_testing_get_json = $('#ab-testing-get').html(),
            get_array = JSON.parse( ab_testing_get_json );

        const getValuePairs = Object.entries(get_array).map(([key, value]) => {
            return `${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
        });
        ab_testing_get += '&'+getValuePairs.join('&');
    }

    $.ajax({
        type: "POST",
        url: do_mhaAbTesting.ajaxurl,
        data: { 
            action: 'mha_ab_redirect_logic',
            data: ab_testing_data,
            get: ab_testing_get,
            referrer: document.referrer
        },
        success: function( results ) {			
            
            if(results){

                var res = JSON.parse(results);
                //console.log(res);

                // Debug Display
                if(res.show_debug){
                    $('body').not('.wp-admin').prepend(res.debug_log);
                }

                // Redirect
                if(res.redirect){
                    window.location.replace(res.redirect);
                    /*
                    let form = document.createElement('form');
                    form.action = res.redirect;
                    form.method = 'post';

                    let hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'ab_redirect';
                    hiddenInput.value = res.redirect;

                    form.appendChild(hiddenInput);
                    document.body.appendChild(form);
                    form.submit();
                    */
                }

            }

        },
        error: function(xhr, ajaxOptions, thrownError){
            console.error(xhr,thrownError);
        }
    });	

});