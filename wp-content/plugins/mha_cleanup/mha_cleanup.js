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
            
        // Disable flag button
        $('#mha-cleanup-data-begin').prop('disabled', true).text('Processing...');
        $('#mha-cleanup-error').html('').addClass('hidden');
        $('#cleanup-deleted-container').slideDown();

        // Start Counter

        // Start the log
        /*
        var form_ids = $('input[name="form_ids"]').val(),
            form_id_array = form_ids.split(",");
        $('#cleanup-status').append('<br />Processing Form #'+form_id_array[0]);   
        */

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
                        $('#mha-cleanup-error').html(res.error).removeClass('hidden');  
                        $('#mha-cleanup-data-begin').prop('disabled', false).text('Are You Sure?').addClass('hidden');
                        $('#mha-start-clean-up').removeClass('hidden');
                    } else {
                        $('#cleanup-progress').slideDown();
                        $('#cleanup-progress .bar').css('width', res.percent+'%');
                        $('#cleanup-progress .label-number').html( res.percent );   
                        
                        // Spit out JSON for later 
                        $('#cleanup-json-storage').append('<textarea class="group">'+res.entries+'</textarea>');

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
        
        if(res.error){

            // Error
            console.error(res);
            $('#mha-cleanup-error').html(res.error).removeClass('hidden');  
            $('#cleanup-status').append('<br />Error...'+res.error);
            $('#mha-cleanup-data-begin').prop('disabled', false).text('Are You Sure?').addClass('hidden');
            $('#mha-start-clean-up').removeClass('hidden');      

        } else {

            //console.log('Else...');
            
            if(res.next_page != null){

                //console.log('Next...');

                // Continue Normal Paging
                var args_2 = 'next_page=' + res.next_page;
                args_2 += '&start_date=' + res.start_date;
                args_2 += '&end_date=' + res.end_date;
                args_2 += '&deleted_entries=' + res.deleted_entries;
                args_2 += '&form_ids=' + res.form_ids;

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

                        // Spit out JSON for later 
                        $('#cleanup-json-storage').append('<textarea class="group">'+res2.entries+'</textarea>');

                        mhaCleanupLooper( res2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);        
                    }
                });	

            } else {

                //console.log('Cleaning...');

                // Export is done, start cleaning up
                $('#cleanup-progress .bar').css('width', '99%');  
                $('#cleanup-progress .bar').css('background-color', '#1fb4bb');
                $('#cleanup-progress .label-number').html( '99' );  
                $('#cleanup-status').append(res.log);   
                
                mhaCleanGroups( res );

            }

        }
    }


    function mhaCleanGroups( res ){
        
        //console.log('Clean group...');

        // Loop through our JSON
        if($('#cleanup-json-storage .group').length){  

            var $thisGroup = $('#cleanup-json-storage .group:first');

            $.ajax({
                type: "POST",
                url: do_mhacleanups.ajaxurl,
                data: { 
                    action: 'mhaCleanerJsonScrubber',
                    data: $thisGroup.val()
                },
                success: function( scrub_results ) {

                    var results = JSON.parse(scrub_results); 
                    $thisGroup.remove();
                    
                    var cleanup_total = parseInt($('#cleanup-deleted').text());
                    $('#cleanup-deleted').html( cleanup_total + results.deleted_entries ); 

                    if($('#cleanup-json-storage .group').length){   
                        
                        // There are still groups
                        mhaCleanGroups( res );

                    } else {

                        // No more groups, how to proceed...
                        if(res.form_ids && res.form_ids.length > 0){   
                            // Start cleaning the next form ID                         
                            mhaCleanGroupsSwitcher( res );    
                        } else {                            
                            // All done!
                            mhaCleanGroupsCloser();
                        }
                        

                    }
                },
                error: function(xhr, ajaxOptions, thrownError){                
                    console.error(xhr,thrownError);
                }    
            
        
            });

        } else {

            //console.log('Group loop 2...');
            if(res.form_ids && res.form_ids.length > 0){                           
                mhaCleanGroupsSwitcher( res );    
            } else {                            
                mhaCleanGroupsCloser();
            }

        }
    
    }

    
    function mhaCleanGroupsSwitcher( res ){
        //console.log('Switching...');

        // Finished one form, move on to the next
        //$('#cleanup-status').append('<br />Processing form #'+res.form_ids[0]+'...');
    
        $('#cleanup-progress .bar').css('width', '0%');
        $('#cleanup-progress .bar').css('background-color', '#007BA7');
        $('#cleanup-progress .label-number').html( '0' );    
        res.page = null;
        res.next_page = 1;
        //console.log(res);
        mhaCleanupLooper(res);  
    }


    function mhaCleanGroupsCloser(){    
        //console.log('Done!');

        // All done, wrap it up
        $('#cleanup-progress .bar').css('width', '100%');
        $('#cleanup-progress .bar').css('background-color', '#f89941');
        $('#cleanup-progress .label-number').html( '100' );     
        $('#cleanup-status').append('<br /><strong>Done!</strong>');
        $('#cleanup-json-storage').html('');
        $('#mha-cleanup-data-begin').prop('disabled', false).text('Are You Sure?').addClass('hidden');
        $('#mha-start-clean-up').removeClass('hidden');
    }


    /**
     * User Cleanup
     */
    
    
    /**
     * Confirmation button click
     */
     $(document).on('click', '#mha-start-userclean-up', function(event){
        event.preventDefault();
        $('#mha-start-userclean-up').addClass('hidden');
        $('#mha-usercleanup-data-begin').removeClass('hidden');
    });

    /**
     * Review cleanup
     */
    $(document).on('click', '#mha-start-userclean-up-review', function(event){

        // Disable default form submit
        event.preventDefault();

        // Vars
        var args = $('#mha-usercleanup').serialize();
        args += '&review=true';
            
        // Disable flag button
        $('#mha-start-userclean-up-review').prop('disabled', true).text('Processing...');
        $('#mha-usercleanup-error').html('').addClass('hidden');

        $.ajax({
            type: "POST",
            url: do_mhacleanups.ajaxurl,
            data: { 
                action: 'mhausercleanupper',
                data: args
            },
            success: function( results ) {
                var res = JSON.parse(results); 
                if(results){
                    if(!res.error){
                        $('#usercleanup-status').removeClass('notice-error').addClass('notice-success').html(res.message).removeClass('hidden');  
                        $('#mha-usercleanup-data-begin').prop('disabled', false).text('Are You Sure?').addClass('hidden');

                        $('#mha-start-userclean-up').removeClass('hidden');                       
                    } else {
                        // Spit out JSON for later 
                        $('#usercleanup-status').removeClass('notice-success').addClass('notice-error').html('<p>'+res.error+'</p>').removeClass('hidden');  
                    }
                }
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	
    });	

    /**
     * Start cleanup
     */
    $(document).on('click', '#mha-usercleanup-data-begin', function(event){

        // Disable default form submit
        event.preventDefault();

        // Vars
        var args = $('#mha-usercleanup').serialize();
            
        // Disable flag button
        $('#mha-usercleanup-data-begin').prop('disabled', true).text('Processing...');
        $('#mha-usercleanup-error').html('').addClass('hidden');

        $.ajax({
            type: "POST",
            url: do_mhacleanups.ajaxurl,
            data: { 
                action: 'mhausercleanupper',
                data: args
            },
            success: function( results ) {
                var res = JSON.parse(results); 
                if(results){
                    if(!res.error){
                        $('#usercleanup-status').removeClass('notice-error').addClass('notice-success').html(res.message).removeClass('hidden');  
                        $('#mha-usercleanup-data-begin').prop('disabled', false).text('Are You Sure?').addClass('hidden');
                        $('#mha-start-userclean-up').removeClass('hidden');
                    } else {
                        // Spit out JSON for later 
                        $('#usercleanup-status').removeClass('notice-success').addClass('notice-error').html('<p>'+res.error+'</p>').removeClass('hidden');  
                    }
                }
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	

    });

});
