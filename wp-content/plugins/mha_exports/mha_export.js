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
                var args_2 = 'paged=' + res.next_page + '&filename=' + res.filename + '&exclude=' + exclude;
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
                $('#submit-aggregate-data-export').prop('disabled', false).text('Download');	
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

                var res = JSON.parse(results);
                $('#aggregate-progress').slideDown();
                $('#aggregate-progress .bar').css('width', res.percent+'%');
                $('#aggregate-progress .label-number').html( res.percent );

                aggregateLooper( results );                
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

                // Continue Paging
                var args_2 = 'page=' + res.next_page;
                    args_2 += '&filename=' + res.filename;
                    args_2 += '&export_screen_start_date=' + res.export_screen_start_date;
                    args_2 += '&export_screen_end_date=' + res.export_screen_end_date;
                    args_2 += '&export_screen_ref=' + res.export_screen_ref;
                    args_2 += '&export_screen_form=' + res.export_screen_form;
                    args_2 += '&export_screen_duplicates=' + res.export_screen_duplicates;
                    args_2 += '&export_screen_spam=' + res.export_screen_spam;
                    args_2 += '&elapsed_start=' + res.elapsed_start;
                    args_2 += '&all_forms=' + res.all_forms;
                    //args_2 += '&field_labels=' + res.field_labels;

                $.ajax({
                    type: "POST",
                    url: do_mhaThoughts.ajaxurl,
                    data: { 
                        action: 'mha_export_screen_data',
                        data: args_2
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
                
                if(res.all_forms){
                    var all_forms_array = JSON.parse(res.all_forms);
                    if(Object.keys(all_forms_array).length > 0 && res.all_forms_continue == 1){                    
                        var continue_params = [];
                            continue_params['all_forms'] = res.all_forms;
                        screenExportDataStart( continue_params );                    
                        $('#screen-exports-progress .bar').css('width', '0%');
                        $('#screen-exports-progress .bar').css('background-color', '');
                        $('#screen-exports-progress .label-number').html( 'Calculating...' );   
                    }
                } else {       
                    $('#screen-exports-progress .bar').css('width', '100%');
                    $('#screen-exports-progress .bar').css('background-color', '#f89941');
                    $('#screen-exports-download').slideDown().append('<li>Done!</li>');
                }

            }

        }
    }

    function screenExportDataStart( params ){
        
        // Form fields args
        var args = $('#mha-all-screen-exports').serialize();

        // All forms override
        if(params.all_forms){
            args += '&all_forms=' + params.all_forms;
        }

        console.log(args);

        $('#export_screen_link').prop('disabled', true).text('Processing...');
        $('#screen-exports-progress .bar').css('background-color', '');
        $('#screen-exports-progress .label-number').html( 'Calculating...' );  
        $('#screen-export-error').html('');

        $.ajax({
            type: "POST",
            url: do_mhaThoughts.ajaxurl,
            data: { 
                action: 'mha_export_screen_data',
                data: args
            },
            success: function( results ) {
                
                var res = JSON.parse(results);
                $('#screen-exports-progress').slideDown();
                $('#screen-exports-progress .bar').css('width', res.percent+'%');
                $('#screen-exports-progress .label-number').html( res.percent );   
                screenExportDataLooper( results );

            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	
        
    }

    $(document).on("submit", '#mha-all-screen-exports', function(event){
        
        // Disable default form submit
        event.preventDefault();

        var all_forms = [];
        if($('select[name="export_screen_form"]').val() == 'all'){
            var form_ids = [];
            $('select[name="export_screen_form"] option').each(function() {
                if($(this).val() != 'all'){
                    form_ids.push($(this).val());
                }
            });
            all_forms['all_forms'] = JSON.stringify(form_ids);
        }
        screenExportDataStart( all_forms );        

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
                var args_2 = 'paged=' + res.next_page + '&filename=' + res.filename + '&manual_users=' + res.manual_users;
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
                $('#submit-nonaggregate-data-export').prop('disabled', false).text('Download');	
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

                var res = JSON.parse(results);
                $('#nonaggregate-progress').slideDown();
                $('#nonaggregate-progress .bar').css('width', res.percent+'%');
                $('#nonaggregate-progress .label-number').html( res.percent );

                nonAggregateLooper( results );                
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	

    });

    

})( jQuery );