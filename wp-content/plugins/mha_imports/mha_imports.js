jQuery(function ($) {
    

    /**
     * Form Submit
     */
    $(document).on('click', '#mha-provider-import-submit', function(event){

        // Disable default form submit
        event.preventDefault();

        // Vars
        var args = $('#mha-provider-import').serialize();
            
        // Disable flag button
        $('#submit-aggregate-data-export').prop('disabled', true).text('Processing...');
        $('#provider-import-error').html('');
        
        // Process form/file data
        var file_data = jQuery('#import_provider_file').prop('files')[0];
        var form_data = new FormData();
        form_data.append('file', file_data);
        form_data.append('action', 'mhaImporterUploader');

        $.ajax({
            type: "POST",
            url: do_mhaImports.ajaxurl,
            type: 'post',
            contentType: false,
            processData: false,
            data: form_data,
            success: function( results ) {
                var res = JSON.parse(results);    
                mhaProviderImportLooper(results);    
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	

    });


    /**
     * Provider Looper
     */
    function mhaProviderImportLooper( results ){

        var res = JSON.parse(results);
            
        if(res.error){

            // Error
            $('#provider-import-error').html(res.error);        

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
                        $('#provider-imports-progress').slideDown();
                        $('#provider-imports-progress .bar').css('width', res.percent+'%');
                        $('#provider-imports-progress .label-number').html( res.percent );           
                        mhaProviderImportLooper( results_2 );
                    },
                    error: function(xhr, ajaxOptions, thrownError){                        
                        console.error(xhr,thrownError);        
                    }
                });	

            } else {

                // Export is done
                $('#provider-imports-status').append('<br />Done!')

            }

        }
    }


});