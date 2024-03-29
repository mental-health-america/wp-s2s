(function( $ ) {

    /**
     * DIY Tool Data Export
     */

    function diyToolExportDataLooper( results ){

        var res = JSON.parse(results);

        if(res.error){

            // Error
            $('#diyTool-export-error').html(res.error);        

        } else {
            
            if(res.next_page != ''){

                $.ajax({
                    type: "POST",
                    url: do_mhaThoughts.ajaxurl,
                    data: { 
                        action: 'mha_export_diy_tool_data',
                        data: res,
                        start: 0
                    },
                    success: function( results_2 ) {  
                        var res = JSON.parse(results_2);
                        //console.log(res);
                        $('#diyTool-exports-progress').slideDown();
                        $('#diyTool-exports-progress .bar').css('width', res.percent+'%');
                        $('#diyTool-exports-progress .label-number').html( res.percent );         
                        diyToolExportDataLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);
                    }
                });	

            } else {

                // Export is done 
                if(!res.export_single){ 
                    $('#export_diy_link').prop('disabled', false).text('Download');	
                    $('#diyTool-exports-download').slideDown().append('<li><strong>Download:</strong> <a target="_blank" href="'+res.download+'">'+res.download+'</a><br /><strong>Elapsed Time:</strong> '+res.total_elapsed_time)+'</li>';
                }
                
                if(res.all_forms_continue == 1){    
                    $('#mha-diy-tool-export input[name="all_forms"]').val(res.all_forms);
                    
                    //console.log(res);
                    diyToolExportDataStart( 1 );   

                    $('#diyTool-exports-progress .bar').css('width', '0%');
                    $('#diyTool-exports-progress .bar').css('background-color', '');
                    $('#diyTool-exports-progress .label-number').html( 'Calculating...' );   
                } else {       
                    $('#diyTool-exports-progress .bar').css('width', '100%');
                    $('#diyTool-exports-progress .bar').css('background-color', '#f89941').removeClass('loading');
                    $('#diyTool-exports-download').slideDown().append('<li>Done!</li>');
                }

            }

        }
    }

    function diyToolExportDataStart( all_loop_checker = null ){
        
        // All Forms loop check
        if(all_loop_checker == 1){
            
            var all_forms = $('#mha-diy-tool-export input[name="all_forms"]').val(),
                form_ids = all_forms.split(',');

            $('#mha-diy-tool-export input[name="form_id"]').val( form_ids.shift() );
            $('#mha-diy-tool-export input[name="all_forms"]').val( form_ids.join() );  

        } else {

            if($('#mha-diy-tool-export .form-checkboxes:checked').length > 1){
                // Start of a multi form export
                var form_ids = new Array();
                $('#mha-diy-tool-export .form-checkboxes:checked').each(function(e){
                    form_ids.push( $(this).val() );
                });
                $('#mha-diy-tool-export input[name="form_id"]').val( form_ids.shift() );
                $('#mha-diy-tool-export input[name="all_forms"]').val( form_ids.join() );
            } else {
                // Single checkbox checked
                $('#mha-diy-tool-export input[name="form_id"]').val( $('.form-checkboxes:checked').val() );
            }

        }

        var args = $('#mha-diy-tool-export').serialize();
        
        $('#export_diy_link').prop('disabled', true).text('Processing...');
        $('#diyTool-exports-progress .bar').css('background-color', '').addClass('loading');
        $('#diyTool-exports-progress .label-number').html( 'Calculating...' );  
        $('#diyTool-export-error').html('');

        $.ajax({
            type: "POST",
            url: do_mhaThoughts.ajaxurl,
            data: { 
                action: 'mha_export_diy_tool_data',
                data: args,
                start: 1
            },
            success: function( results ) {
                
                if(results){
                    var res = JSON.parse(results);
                    //console.log(res);
                    if(res.error){
                        alert(res.error+' Please refresh this page and try again.');
                    } else {
                        $('#diyTool-exports-progress').slideDown();
                        $('#diyTool-exports-progress .bar').css('width', res.percent+'%');
                        $('#diyTool-exports-progress .label-number').html( res.percent );   
                        diyToolExportDataLooper( results );
                    }
                } else {                
                    $('#diyTool-exports-progress').slideDown();
                    $('#diyTool-exports-progress .bar').css('width', '100%');
                    $('#diyTool-exports-progress .bar').css('background-color', '#ed5d66').removeClass('loading');
                    $('#diyTool-exports-progress .label-number').html( '100' );   
                    $('#diyTool-exports-download').slideDown().append('<li>No data available for this query.</li>');
                }

            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	
        
    }

    $(document).on("submit", '#mha-diy-tool-export', function(event){
        event.preventDefault();
        diyToolExportDataStart();    
    });
    
})( jQuery );