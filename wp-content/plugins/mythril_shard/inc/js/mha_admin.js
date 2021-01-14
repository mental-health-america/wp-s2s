jQuery(function ($) {

    /**
     * Indent checkbox fields
     */
    var checkExist = setInterval(function() {

        if ($('.gform_export_field').length) {
            
            $('.gform_export_field').each(function(){
                var val = $(this).val();
                if(val.includes('.')) {
                    $(this).parent('li').css('margin-left', '30px');
                }
            });

            clearInterval(checkExist);
        }

     }, 100); 

});