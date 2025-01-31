(function( $ ) {

    /**
     * Callrail Export 
     */

    function callrailctaExportDataLooper( results ){

        var res = JSON.parse(results);
        console.log(res);

        if(res.error){

            // Error
            $('#callrailcta-export-error').html(res.error);        

        } else {
            
            if(res.next_page != ''){

                $.ajax({
                    type: "POST",
                    url: do_mhacallrailctaExport.ajaxurl,
                    data: { 
                        action: 'mha_export_callrailcta',
                        data: res,
                        start: 0
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);
                        $('#callrailcta-exports-progress').slideDown();
                        $('#callrailcta-exports-progress .bar').css('width', res.percent+'%');
                        $('#callrailcta-exports-progress .label-number').html( res.percent );         
                        callrailctaExportDataLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);
                    }
                });	

            } else {

                $('#callrailcta-exports-progress .bar').css('width', '100%');
                $('#callrailcta-exports-progress .bar').css('background-color', '#f89941').removeClass('loading');
                $('#export_callrailcta_link').prop('disabled', false).text('Download');	
                $('#callrailcta-exports-download').slideDown().append('<li><strong>Download:</strong> <a target="_blank" href="'+res.download+'">'+res.download+'</a><br /><strong>Elapsed Time:</strong> '+res.total_elapsed_time)+'</li>';
                
            }

        }
    }

    function callrailctaExportDataStart(){
        
        var args = $('#mha-callrail-cta-export').serialize();

        $('#export_callrailcta_link').prop('disabled', true).text('Processing...');
        $('#callrailcta-exports-progress .bar').css('background-color', '').addClass('loading');
        $('#callrailcta-exports-progress .label-number').html( 'Calculating...' );  
        $('#callrailcta-export-error').html('');

        $.ajax({
            type: "POST",
            url: do_mhacallrailctaExport.ajaxurl,
            data: { 
                action: 'mha_export_callrailcta',
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
                        $('#callrailcta-exports-progress').slideDown();
                        $('#callrailcta-exports-progress .bar').css('width', res.percent+'%');
                        $('#callrailcta-exports-progress .label-number').html( res.percent );   

                        if(res.next_page != ''){
                            callrailctaExportDataLooper( results );
                        } else {
                            $('#callrailcta-exports-progress .bar').css('width', '100%');
                            $('#callrailcta-exports-progress .bar').css('background-color', '#f89941').removeClass('loading');
                            $('#export_callrailcta_link').prop('disabled', false).text('Download');	
                            $('#callrailcta-exports-download').slideDown().append('<li><strong>Download:</strong> <a target="_blank" href="'+res.download+'">'+res.download+'</a><br /><strong>Elapsed Time:</strong> '+res.total_elapsed_time)+'</li>';
                        }
                    }
                } else {                
                    $('#callrailcta-exports-progress').slideDown();
                    $('#callrailcta-exports-progress .bar').css('width', '100%');
                    $('#callrailcta-exports-progress .bar').css('background-color', '#f89941').removeClass('loading');
                    $('#callrailcta-exports-progress .label-number').html( '100' );   
                    $('#callrailcta-exports-download').slideDown().append('<li>No data available for this query.</li>');
                }

            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	
        
    }

    $(document).on("submit", '#mha-callrail-cta-export', function(event){
        event.preventDefault();
        callrailctaExportDataStart();    
    });
    
})( jQuery );