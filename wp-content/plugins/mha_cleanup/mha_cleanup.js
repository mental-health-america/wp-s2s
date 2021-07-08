jQuery(function ($) {
    

    /**
     * Confirmation button click
     */
    $(document).on('click', '#mha-start-clean-up', function(event){
        event.preventDefault();
        $('#mha-start-clean-up').addClass('hidden');
        $('#mha-cleanup-data-begin').removeClass('hidden');
    });

    /**
     * Start cleanup
     */
    $(document).on('click', '#mha-cleanup-data-begin', function(event){

        // Disable default form submit
        event.preventDefault();

        // Vars
        var args = $('#mha-cleanup').serialize();
        console.log(args);
            
        // Disable flag button
        $('#mha-cleanup-data-begin').prop('disabled', true).text('Processing...');
        $('#mha-cleanup-error').html('');
        
        $.ajax({
            type: "POST",
            url: do_mhacleanups.ajaxurl,
            data: { 
                action: 'mhacleanuperLooper',
                data: args
            },
            success: function( results ) {
                var res = JSON.parse(results);  
                if(results){
                    if(res.error){
                        console.error(res);
                        $('#mha-cleanup-error').html(res.error);  
                        $('#mha-cleanup-data-begin').prop('disabled', false).text('Are You Sure?').addClass('hidden');
                        $('#mha-start-clean-up').removeClass('hidden');
                    } else {
                        $('#cleanup-progress').slideDown();
                        $('#cleanup-progress .bar').css('width', res.percent+'%');
                        $('#cleanup-progress .label-number').html( res.percent );     
                        $('#provider-imports-status').append(res.log);  
                        mhaCleanupLooper(res);    
                    }
                }
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	

    });


    /**
     * Provider Looper
     */
    function mhaCleanupLooper( res ){
        
        console.log(res);
        if(res.error){

            // Error
            console.error(res);
            $('#mha-cleanup-error').html(res.error);  
            $('#cleanup-status').append('<br />Error...'+res.error);
            $('#mha-cleanup-data-begin').prop('disabled', false).text('Are You Sure?').addClass('hidden');
            $('#mha-start-clean-up').removeClass('hidden');      

        } else {
            
            if(res.next_page != null){

                // Continue Paging
                var args_2 = 'next_page=' + res.next_page;
                args_2 += '&start_date=' + res.start_date;
                args_2 += '&end_date=' + res.end_date;
                args_2 += '&deleted_entries=' + res.deleted_entries;

                $.ajax({
                    type: "POST",
                    url: do_mhacleanups.ajaxurl,
                    data: { 
                        action: 'mhacleanuperLooper',
                        data: args_2
                    },
                    success: function( results ) {  
                        var res2 = JSON.parse(results);	
                        $('#cleanup-progress').slideDown();
                        $('#cleanup-progress .bar').css('width', res2.percent+'%');
                        $('#cleanup-progress .label-number').html( res2.percent );     
                        $('#provider-imports-status').append(res2.log);     
                        mhaCleanupLooper( res2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);        
                    }
                });	

            } else {

                // Export is done
                $('#cleanup-progress .bar').css('width', '100%');
                $('#cleanup-progress .bar').css('background-color', '#f89941');
                $('#cleanup-status').append('<br />Done!');
                $('#mha-cleanup-data-begin').prop('disabled', false).text('Are You Sure?').addClass('hidden');
                $('#mha-start-clean-up').removeClass('hidden');

            }

        }
    }


});