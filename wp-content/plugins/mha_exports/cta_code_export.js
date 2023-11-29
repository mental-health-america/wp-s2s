(function( $ ) {

    /**
     * CTA Codes Data Export
     */

    function ctacodesExportDataLooper( results ){

        var res = JSON.parse(results);

        if(res.error){

            // Error
            $('#cta-codes-export-error').html(res.error);        

        } else {
            
            if(res.next_page != ''){

                $.ajax({
                    type: "POST",
                    url: do_mhaThoughts.ajaxurl,
                    data: { 
                        action: 'mha_export_cta_codes',
                        data: res,
                        start: 0
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);
                        $('#ctaCodes-exports-progress').slideDown();
                        $('#ctaCodes-exports-progress .bar').css('width', res.percent+'%');
                        $('#ctaCodes-exports-progress .label-number').html( res.percent );         
                        ctacodesExportDataLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);
                    }
                });	

            } else {

                // Export is done 
                if(!res.export_single){ 
                    $('#export_ctdacodes_link').prop('disabled', false).text('Download');	
                    $('#ctaCodes-exports-download').slideDown().append('<li><strong>Download:</strong> <a target="_blank" href="'+res.download+'">'+res.download+'</a><br /><strong>Elapsed Time:</strong> '+res.total_elapsed_time)+'</li>';
                }
                
                if(res.all_forms_continue == 1){    
                    $('input[name="all_forms"]').val(res.all_forms);
                    
                    //console.log(res);
                    if(res.export_single == 1){    
                        let single_continue_data = '&export_single_continue=1&filename='+res.filename;
                        ctaCodesExportDataStart( 1, 1, single_continue_data );                    
                    } else {
                        ctaCodesExportDataStart( 1, null );                    
                    }

                    $('#ctaCodes-exports-progress .bar').css('width', '0%');
                    $('#ctaCodes-exports-progress .bar').css('background-color', '');
                    $('#ctaCodes-exports-progress .label-number').html( 'Calculating...' );   
                } else {       
                    $('#ctaCodes-exports-progress .bar').css('width', '100%');
                    $('#ctaCodes-exports-progress .bar').css('background-color', '#f89941').removeClass('loading');
                    $('#ctaCodes-exports-download').slideDown().append('<li>Done!</li>');
                }

            }

        }
    }

    function ctaCodesExportDataStart(){
        
        var args = $('#mha-cta-codes-export').serialize();

        $('#export_ctdacodes_link').prop('disabled', true).text('Processing...');
        $('#ctaCodes-exports-progress .bar').css('background-color', '').addClass('loading');
        $('#ctaCodes-exports-progress .label-number').html( 'Calculating...' );  
        $('#cta-codes-export-error').html('');

        $.ajax({
            type: "POST",
            url: do_mhaThoughts.ajaxurl,
            data: { 
                action: 'mha_export_cta_codes',
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
                        $('#ctaCodes-exports-progress').slideDown();
                        $('#ctaCodes-exports-progress .bar').css('width', res.percent+'%');
                        $('#ctaCodes-exports-progress .label-number').html( res.percent );   
                        ctacodesExportDataLooper( results );
                    }
                } else {                
                    $('#ctaCodes-exports-progress').slideDown();
                    $('#ctaCodes-exports-progress .bar').css('width', '100%');
                    $('#ctaCodes-exports-progress .bar').css('background-color', '#ed5d66').removeClass('loading');
                    $('#ctaCodes-exports-progress .label-number').html( '100' );   
                    $('#ctaCodes-exports-download').slideDown().append('<li>No data available for this query.</li>');
                }

            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	
        
    }

    $(document).on("submit", '#mha-cta-codes-export', function(event){
        event.preventDefault();
        ctaCodesExportDataStart();    
    });
    
})( jQuery );