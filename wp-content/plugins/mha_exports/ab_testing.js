(function( $ ) {

    /**
     * AB Testing Data Export
     */

    function abtestingExportDataLooper( results ){

        var res = JSON.parse(results);
        console.log(res);

        if(res.error){

            // Error
            $('#abtesting-export-error').html(res.error);        

        } else {
            
            if(res.next_page != ''){

                $.ajax({
                    type: "POST",
                    url: do_mhaThoughts.ajaxurl,
                    data: { 
                        action: 'mha_export_ab_testing_data',
                        data: res,
                        start: 0
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);
                        $('#abTesting-exports-progress').slideDown();
                        $('#abTesting-exports-progress .bar').css('width', res.percent+'%');
                        $('#abTesting-exports-progress .label-number').html( res.percent );         
                        abtestingExportDataLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);
                    }
                });	

            } else {

                // Export is done 
                if(!res.export_single){ 
                    $('#export_abtesting_link').prop('disabled', false).text('Download');	
                    $('#abTesting-exports-download').slideDown().append('<li><strong>Download:</strong> <a target="_blank" href="'+res.download+'">'+res.download+'</a><br /><strong>Elapsed Time:</strong> '+res.total_elapsed_time)+'</li>';
                }
                
                if(res.all_forms_continue == 1){    
                    $('input[name="all_forms"]').val(res.all_forms);
                    
                    //console.log(res);
                    if(res.export_single == 1){    
                        let single_continue_data = '&export_single_continue=1&filename='+res.filename;
                        abtestingExportDataStart( 1, 1, single_continue_data );                    
                    } else {
                        abtestingExportDataStart( 1, null );                    
                    }

                    $('#abTesting-exports-progress .bar').css('width', '0%');
                    $('#abTesting-exports-progress .bar').css('background-color', '');
                    $('#abTesting-exports-progress .label-number').html( 'Calculating...' );   
                } else {       
                    $('#abTesting-exports-progress .bar').css('width', '100%');
                    $('#abTesting-exports-progress .bar').css('background-color', '#f89941').removeClass('loading');
                    $('#abTesting-exports-download').slideDown().append('<li>Done!</li>');
                }

            }

        }
    }

    function abtestingExportDataStart(){
        
        var args = $('#mha-ab-testing-export').serialize();

        $('#export_abtesting_link').prop('disabled', true).text('Processing...');
        $('#abTesting-exports-progress .bar').css('background-color', '').addClass('loading');
        $('#abTesting-exports-progress .label-number').html( 'Calculating...' );  
        $('#abtesting-export-error').html('');

        $.ajax({
            type: "POST",
            url: do_mhaThoughts.ajaxurl,
            data: { 
                action: 'mha_export_ab_testing_data',
                data: args,
                start: 1
            },
            success: function( results ) {
                                
                if(results){
                    var res = JSON.parse(results);
                    console.log(res);
                    if(res.error){
                        alert(res.error+' Please refresh this page and try again.');
                    } else {
                        $('#abTesting-exports-progress').slideDown();
                        $('#abTesting-exports-progress .bar').css('width', res.percent+'%');
                        $('#abTesting-exports-progress .label-number').html( res.percent );   
                        abtestingExportDataLooper( results );
                    }
                } else {                
                    $('#abTesting-exports-progress').slideDown();
                    $('#abTesting-exports-progress .bar').css('width', '100%');
                    $('#abTesting-exports-progress .bar').css('background-color', '#ed5d66').removeClass('loading');
                    $('#abTesting-exports-progress .label-number').html( '100' );   
                    $('#abTesting-exports-download').slideDown().append('<li>No data available for this query.</li>');
                }

            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	
        
    }

    $(document).on("submit", '#mha-ab-testing-export', function(event){
        event.preventDefault();
        abtestingExportDataStart();    
    });
    
})( jQuery );