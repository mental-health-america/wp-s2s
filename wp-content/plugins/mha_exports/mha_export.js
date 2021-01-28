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
                $('#submit-aggregate-data-export').prop('disabled', false).text('Export');	
                $('#aggregate-download').slideDown().html('<strong>Download:</strong> <a target="_blank" href="'+download_link+'">'+download_link+'</a><Br />Note: You will probably need to manually remove duplicates.');

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


    $(document).on("click", '#export_screen_link', function(event){
        
        var startDate = $('input#export_screen_start_date').val(),
            endDate = $('input#export_screen_end_date').val(),
            filters = $('input#export_screen_ref').val(),
            href = $('a#export_screen_link').attr('data-orig-href');

        if(startDate != '' || $endDate != '' || $filters != ''){
            href = href+'?';
        }

        if(startDate != ''){
            href = href+'start_date='+startDate;
            if(endDate != '' || filters != ''){
                href = href+'&';
            }
        }

        if(endDate != ''){
            href = href+'end_date='+endDate;
            if(filters != ''){
                href = href+'&';
            }
        }

        if(filters != ''){
            var addFilter = 'filter=53:contains:'+filters+';54:contains:'+filters+';66:contains:'+filters+';47:contains:'+filters+';76:contains:'+filters+';57:contains:'+filters+';55:contains:'+filters+';89:contains:'+filters+';51:contains:'+filters+';66:contains:'+filters+';82:contains:'+filters+';any';
            href = href+addFilter;
        }

        $('#export_screen_link').attr('href', href);

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
                $('#submit-nonaggregate-data-export').prop('disabled', false).text('Export');	
                $('#nonaggregate-download').slideDown().html('<strong>Download:</strong> <a target="_blank" href="'+download_link+'">'+download_link+'</a>');

            }

        }
    }

    $(document).on('click', '#submit-nonaggregate-data-export', function(event){

        // Disable default form submit
        event.preventDefault();

        // Vars
        var args = $('#nonaggregate-data-export').serialize();
            
        // Disable flag button
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