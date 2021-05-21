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
                mhaProviderImportLooper(res);    
            },
            error: function(xhr, ajaxOptions, thrownError){                
                console.error(xhr,thrownError);
            }
        });	

    });


    /**
     * Provider Looper
     */
    function mhaProviderImportLooper( res ){

        console.log(res);
            
        if(res.error){

            // Error
            $('#provider-import-error').html(res.error);        

        } else {

            console.log('Proceeding...');
            
            if(res.next_page != null){

                // Continue Paging
                var args = 'next_page=' + res.next_page + '&file=' + res.file;
                console.log(args);

                $.ajax({
                    type: "POST",
                    url: do_mhaImports.ajaxurl,
                    data: { 
                        action: 'mhaImporterLooper',
                        data: args
                    },
                    success: function( results ) {  
                        console.log(results);
                        var res2 = JSON.parse(results);	
                        console.log(res2);
                        $('#provider-imports-progress').slideDown();
                        $('#provider-imports-progress .bar').css('width', res2.percent+'%');
                        $('#provider-imports-progress .label-number').html( res2.percent );     
                        $('#provider-imports-status').append(res2.log);    
                        //mhaProviderImportLooper( res2 );
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