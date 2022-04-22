(function( $ ) {

    /**
     * Update User Results
     */

     function userScreenDataUpdateLooper( results ){

        var res = JSON.parse(results);
        
        if(res.error){
            // Error
            $('#screen-export-error').html(res.error);            
        } else {
            
            if(res.next_page != ''){

                // Continue Paging
                var args_2 = 'page=' + res.next_page + '&form_id=' + res.form_id;
                $.ajax({
                    type: "POST",
                    url: do_mhaUpdateScreenResults.ajaxurl,
                    data: { 
                        action: 'mha_result_updater_looper',
                        data: args_2
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);
                        $('#result-log').append(res.append);     
                        $('#update-user-results-submit').prop('disabled', true).val('Processing... '+res.percent+'%');	

                        $('#update-screen-results-progress').slideDown();
                        $('#update-screen-results-progress .bar').css('width', res.percent+'%');
                        $('#update-screen-results-progress .label-number').html( res.percent );    

                        userScreenDataUpdateLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);        
                    }
                });	

            } else {

                // Done
                $('#update-user-results-submit').prop('disabled', false).val('Begin Updates');	
                $('#result-log').append('Done!');

            }

        }
    }

    $(document).on("submit", '#mha-update-user-results', function(event){
        
        // Disable default form submit
        event.preventDefault();
        
        var args = $('#mha-update-user-results').serialize();

        $('#update-user-results-submit').prop('disabled', true).val('Processing...');
        $('#screen-export-error').html('');

        $.ajax({
            type: "POST",
            url: do_mhaUpdateScreenResults.ajaxurl,
            data: { 
                action: 'mha_result_updater_looper',
                data: args
            },
            success: function( results ) {
                var res = JSON.parse(results);
                $('#result-log').append(res.append);
                $('#update-user-results-submit').val('Processing... '+res.percent+'%');	

                $('#update-screen-results-progress').slideDown();
                $('#update-screen-results-progress .bar').css('width', res.percent+'%');
                $('#update-screen-results-progress .label-number').html( res.percent );       
                userScreenDataUpdateLooper( results );
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	
        

    });


    

})( jQuery );