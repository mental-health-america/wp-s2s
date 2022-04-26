(function( $ ) {

    /**
     * Aggregate Data Export
     */

    function aggregateLooper( results ){

        var res = JSON.parse(results),
            exclude = res.exclude.toString();
            
        if(res.error){
            // Error
            $('#aggregate-error').html(res.error);            
        } else {
            
            if(res.next_page != ''){

                // Continue Paging
                var args_2 = 'paged=' + res.next_page + '&filename=' + res.filename + '&exclude=' + exclude + '&start_date=' + res.start_date + '&end_date=' + res.end_date;
                $.ajax({
                    type: "POST",
                    url: do_mhaThoughts.ajaxurl,
                    data: { 
                        action: 'mha_aggregate_data_export',
                        data: args_2
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);	
                        $('#aggregate-progress').slideDown();
                        $('#aggregate-progress .bar').css('width', res.percent+'%');
                        $('#aggregate-progress .label-number').html( res.percent );           
                        aggregateLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);        
                    }
                });	

            } else {

                // Export is done
                var download_link = res.download;
                $('#submit-aggregate-data-export').prop('disabled', false).text('Download Aggregate Data');	
                $('#aggregate-download').slideDown().html('<strong>Download:</strong> <a target="_blank" href="'+download_link+'">'+download_link+'</a>');

            }

        }
    }

    $(document).on('click', '#submit-aggregate-data-export', function(event){

        // Disable default form submit
        event.preventDefault();

        // Vars
        var args = $('#aggregate-data-export').serialize();
            
        // Disable flag button
        $('#submit-aggregate-data-export').prop('disabled', true).text('Processing...');
        $('#aggregate-error').html('');
        
        $.ajax({
            type: "POST",
            url: do_mhaThoughts.ajaxurl,
            data: { 
                action: 'mha_aggregate_data_export',
                data: args + '&paged=1'
            },
            success: function( results ) {
                if(results){
                    var res = JSON.parse(results);
                    if(res.error){
                        alert(res.error+' Please refresh this page and try again.');
                    } else {
                        $('#aggregate-progress').slideDown();
                        $('#aggregate-progress .bar').css('width', res.percent+'%');
                        $('#aggregate-progress .label-number').html( res.percent );        
                        aggregateLooper( results );    
                    }
                } else {               
                    alert('No data available for this query. Please refresh this page and try again.');
                }
                
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	

    });


    /**
     * Screening Data Export
     */

    function screenExportDataLooper( results ){

        var res = JSON.parse(results);
        console.log(res);

        if(res.error){

            // Error
            $('#screen-export-error').html(res.error);        

        } else {
            
            if(res.next_page != ''){

                $.ajax({
                    type: "POST",
                    url: do_mhaThoughts.ajaxurl,
                    data: { 
                        action: 'mha_export_screen_data',
                        data: res,
                        start: 0
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);
                        $('#screen-exports-progress').slideDown();
                        $('#screen-exports-progress .bar').css('width', res.percent+'%');
                        $('#screen-exports-progress .label-number').html( res.percent );         
                        screenExportDataLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);
                    }
                });	

            } else {

                // Export is done
                var download_link = res.download;
                $('#export_screen_link').prop('disabled', false).text('Download');	
                $('#screen-exports-download').slideDown().append('<li><strong>Download:</strong> <a target="_blank" href="'+download_link+'">'+download_link+'</a><br /><strong>Elapsed Time:</strong> '+res.total_elapsed_time)+'</li>';
                
                if(res.all_forms_continue == 1){    
                    $('input[name="all_forms"]').val(res.all_forms);
                    screenExportDataStart( 1 );                    
                    $('#screen-exports-progress .bar').css('width', '0%');
                    $('#screen-exports-progress .bar').css('background-color', '');
                    $('#screen-exports-progress .label-number').html( 'Calculating...' );   
                } else {       
                    $('#screen-exports-progress .bar').css('width', '100%');
                    $('#screen-exports-progress .bar').css('background-color', '#f89941').removeClass('loading');
                    $('#screen-exports-download').slideDown().append('<li>Done!</li>');
                }

            }

        }
    }

    function screenExportDataStart( all_loop_checker = null ){

        console.log('Loop Checker', all_loop_checker);
        
        // All Forms loop check
        if(all_loop_checker == 1){
            
            var all_forms = $('input[name="all_forms"]').val(),
                form_ids = all_forms.split(',');

            $('input[name="form_id"]').val( form_ids.shift() );
            $('input[name="all_forms"]').val( form_ids.join() );  

        } else {
            
            if($('.form-checkboxes:checked').length > 1){
                // Start of a multi form export
                var form_ids = new Array();
                $('.form-checkboxes:checked').each(function(e){
                    form_ids.push( $(this).val() );
                });
                $('input[name="form_id"]').val( form_ids.shift() );
                $('input[name="all_forms"]').val( form_ids.join() );
            } else {
                // Single checkbox checked
                $('input[name="form_id"]').val( $('.form-checkboxes:checked').val() );
            }
            
        }

        var args = $('#mha-all-screen-exports').serialize();

        $('#export_screen_link').prop('disabled', true).text('Processing...');
        $('#screen-exports-progress .bar').css('background-color', '').addClass('loading');
        $('#screen-exports-progress .label-number').html( 'Calculating...' );  
        $('#screen-export-error').html('');

        $.ajax({
            type: "POST",
            url: do_mhaThoughts.ajaxurl,
            data: { 
                action: 'mha_export_screen_data',
                data: args,
                start: 1
            },
            success: function( results ) {
                
                if(results){
                    var res = JSON.parse(results);
                    if(res.error){
                        alert(res.error+' Please refresh this page and try again.');
                    } else {
                        $('#screen-exports-progress').slideDown();
                        $('#screen-exports-progress .bar').css('width', res.percent+'%');
                        $('#screen-exports-progress .label-number').html( res.percent );   
                        screenExportDataLooper( results );
                    }
                } else {                
                    $('#screen-exports-progress').slideDown();
                    $('#screen-exports-progress .bar').css('width', '100%');
                    $('#screen-exports-progress .bar').css('background-color', '#ed5d66').removeClass('loading');
                    $('#screen-exports-progress .label-number').html( '100' );   
                    $('#screen-exports-download').slideDown().append('<li>No data available for this query.</li>');
                }

            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	
        
    }

    $(document).on("submit", '#mha-all-screen-exports', function(event){
        event.preventDefault();
        screenExportDataStart();    
    });


    /**
     * Aggregate Data Export
     */
    
    function nonAggregateLooper( results ){

        var res = JSON.parse(results);
            
        if(res.error){
            // Error
            $('#nonaggregate-error').html(res.error);            
        } else {
            
            if(res.next_page != ''){

                // Continue Paging
                var args_2 = 'paged=' + res.next_page + '&filename=' + res.filename + '&manual_users=' + res.manual_users + '&start_date=' + res.start_date + '&end_date=' + res.end_date;
                $.ajax({
                    type: "POST",
                    url: do_mhaThoughts.ajaxurl,
                    data: { 
                        action: 'mha_nonaggregate_data_export',
                        data: args_2
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);	
                        $('#nonaggregate-progress').slideDown();
                        $('#nonaggregate-progress .bar').css('width', res.percent+'%');
                        $('#nonaggregate-progress .label-number').html( res.percent );           
                        nonAggregateLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);        
                    }
                });	

            } else {

                // Export is done
                var download_link = res.download;
                $('#submit-nonaggregate-data-export').prop('disabled', false).text('Download Non-Aggregate Data');	
                $('#nonaggregate-download').slideDown().html('<strong>Download:</strong> <a target="_blank" href="'+download_link+'">'+download_link+'</a>');

            }

        }
    }

    $(document).on('click', '#submit-nonaggregate-data-export', function(event){

        // Disable default form submit
        event.preventDefault();

        // Vars
        var args = $('#nonaggregate-data-export').serialize();
            
        // Disable submit button
        $('#submit-nonaggregate-data-export').prop('disabled', true).text('Processing...');
        $('#nonaggregate-error').html('');
        
        $.ajax({
            type: "POST",
            url: do_mhaThoughts.ajaxurl,
            data: { 
                action: 'mha_nonaggregate_data_export',
                data: args + '&paged=1'
            },
            success: function( results ) {
                if(results){
                    var res = JSON.parse(results);
                    if(res.error){
                        alert(res.error+' Please refresh this page and try again.');
                    } else {
                        $('#nonaggregate-progress').slideDown();
                        $('#nonaggregate-progress .bar').css('width', res.percent+'%');
                        $('#nonaggregate-progress .label-number').html( res.percent );
                        nonAggregateLooper( results );  
                    }
                } else {                    
                    alert('No data available for this query. Please refresh this page and try again.');
                }
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	

    });


    
    function userExportLooper( results ){

        var res = JSON.parse(results);
            
        if(res.error){
            // Error
            $('#user-export-error').html(res.error);            
        } else {
            
            if(res.next_page != ''){

                // Continue Paging
                var args_2 = 'paged=' + res.next_page + '&filename=' + res.filename;
                $.ajax({
                    type: "POST",
                    url: do_mhaThoughts.ajaxurl,
                    data: { 
                        action: 'mha_user_data_export',
                        data: args_2
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);	
                        $('#user-exports-progress').slideDown();
                        $('#user-exports-progress .bar').css('width', res.percent+'%');
                        $('#user-exports-progress .label-number').html( res.percent );           
                        userExportLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);        
                    }
                });	

            } else {

                // Export is done
                var download_link = res.download;
                $('#export_user_link').prop('disabled', false).text('Download User Data');	
                $('#user-exports-download').slideDown().html('<strong>Download:</strong> <a target="_blank" href="'+download_link+'">'+download_link+'</a>');

            }

        }
    }

    $(document).on('click', '#export_user_link', function(event){

        // Disable default form submit
        event.preventDefault();

        // Vars
        var args = $('#mha-user-exports').serialize();
            
        // Disable flag button
        $('#export_user_link').prop('disabled', true).text('Processing...');
        $('#user-export-error').html('');
        
        $.ajax({
            type: "POST",
            url: do_mhaThoughts.ajaxurl,
            data: { 
                action: 'mha_user_data_export',
                data: args + '&paged=0'
            },
            success: function( results ) {
                if(results){
                    var res = JSON.parse(results);
                    if(res.error){
                        alert(res.error+' Please refresh this page and try again.');
                    } else {
                        $('#user-exports-progress').slideDown();
                        $('#user-exports-progress .bar').css('width', res.percent+'%');
                        $('#user-exports-progress .label-number').html( res.percent );        
                        userExportLooper( results );    
                    }
                } else {                    
                    alert('No data available for this query. Please refresh this page and try again.');
                }
                
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	

    });

    

})( jQuery );