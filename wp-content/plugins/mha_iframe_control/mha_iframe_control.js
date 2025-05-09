jQuery(document).ready(function(){

	//allow level change
	jQuery('#allowlevel').change(function(){
		jQuery('#ckfaupdate').prop('disabled', false);
		if(jQuery("#allowlevel").val() == 'allowsome'){
			jQuery('#ckfa-url-input-con').show();
		}else{
			jQuery('#ckfa-url-input-con').hide();
		}
	});

	//adding in a site
	jQuery('#ckfa-url-input').change(function() {
		jQuery('#ckfaupdate').prop('disabled', false);
	});

	//checking or unchecking a site
	jQuery('input:checkbox').change(function(){
		jQuery('#ckfaupdate').prop('disabled', false);
	});
});