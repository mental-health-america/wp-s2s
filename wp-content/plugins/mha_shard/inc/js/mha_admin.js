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

     $('#export_form').change(function(event){

        setTimeout(function(){

            var checkExist2 = setInterval(function() {

                if ($('.gform_export_field').length) {
                    
                    $('.gform_export_field').each(function(){
                        var val = $(this).val();
                        if(val.includes('.')) {
                            $(this).parent('li').css('margin-left', '30px');
                        }
                    });
        
                    clearInterval(checkExist2);
                }
        
             }, 100); 
            
        }, 1000);
        

     });

  /**
   * API Log Viewer
   */
  const $anonTable   = $('#anon-tracking-data[datatables-enable]');
  const apiLogTable = $anonTable.DataTable({
    dom: 'Blfrtip',
    ajax: {
      url: window.ajaxurl+'?action=mha_get_api_log_entries_SSP',
      type: 'POST',
      data: (dtParams) => {
        dtParams.minDate = $('#min').val();
        dtParams.maxDate = $('#max').val();
      }
    },
    // data: displayData,
    processing: true,
    serverSide: true,
    pageLength: 50,
    lengthMenu: [[10, 50, 250, 500, 1000, 5000, -1], [10, 50, 250, 500, 1000, 5000, 'All']],
    buttons: [
      { extend: 'csvHtml5', exportOptions: { stripHtml: false } },
      { extend: 'excelHtml5', exportOptions: { stripHtml: false, decodeEntities: false } }
    ],
    columns: [
      { data: 'id' },
      { data: 'api_key' },
      { data: 'source_ip' },
      { data: 'sid' },
      { data: 'http_response_code' },
      { data: 'http_response_message' },
      {
        data: 'accessed_on',
        searchable: false
      },
    ],
    order: [[0, 'desc']],
    language: {
      emptyTable: 'No log entries found.'
    }
  });

  // DATE FILTERS
  // Create date inputs
  minDate = new DateTime('#min', {
    format: 'YYYY-MM-DD HH:mm:ss'
  });
  maxDate = new DateTime('#max', {
    format: 'YYYY-MM-DD HH:mm:ss'
  });

  // Redraw table onChange
  $('#min, #max').each((el) => {
    $(this).on('change', () => apiLogTable.draw());
  });

});