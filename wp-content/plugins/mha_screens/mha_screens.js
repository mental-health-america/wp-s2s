jQuery(function ($) {

	// Footer Form Submit Actions
	if($('#email-screening-results').length){
	
        $.validator.addMethod("checkEmail", function(value, element) {
			var re = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;
			// var re = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|academy|accountants|agency|apartments|associates|bargains|bike|bingo|boutique|builders|business|cab|cafe|camera|camp|capital|cards|care|careers|cash|casino|catering|center|chat|cheap|church|city|claims|cleaning|clinic|clothing|coach|codes|coffee|community|company|computer|condos|construction|contractors|cool|coupons|credit|creditcard|cruises|dating|deals|delivery|dental|diamonds|digital|direct|directory|discount|dog|domains|education|email|energy|engineering|enterprises|equipment|estate|events|exchange|expert|exposed|express|fail|farm|finance|financial|fish|fitness|flights|florist|football|foundation|fund|furniture|fyi|gallery|gifts|glass|gold|golf|graphics|gratis|gripe|guide|guru|healthcare|hockey|holdings|holiday|house|immo|industries|institute|insure|international|investments|jewelry|kitchen|land|lease|legal|life|lighting|limited|limo|loans|maison|management|marketing|mba|media|memorial|money|movie|network|partners|parts|photography|photos|pictures|pizza|place|plumbing|plus|productions|properties|recipes|reisen|rentals|repair|report|restaurant|run|sarl|school|schule|services|shoes|show|singles|soccer|solar|solutions|style|supplies|supply|support|surgery|systems|tax|taxi|team|technology|tennis|theater|tienda|tips|tires|today|tools|tours|town|toys|training|university|vacations|ventures|viajes|villas|vin|vision|voyage|watch|wine|works|world|wtf|zone|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i;
            return this.optional(element) || re.test(value.trim());
        }, 'Invalid email address.'); 
		
		
 		// Resets in case of refresh
		$('#email-screening-results .submit').prop('disabled', false);

		$("#email-screening-results").validate({
			rules: {
				email: {
					// email: true,
					checkEmail: true
				}
			},
			submitHandler: function(form) {

				// Prep the data
				var data = $(form).serialize();

				// Form submitted class
				$(form).addClass('submitted');

				// Disable submit
				$('.submit',form).prop('disabled', true).addClass('loading');

				$.ajax({
					type: "POST",
					url: do_mhaScreenEmail.ajaxurl,
					data: { 
						action: 'mhaScreenEmail',
						data: data
					},
					success: function( results ) {
						
						$(form).addClass('success');
						if($('.submit',form).hasClass('espanol')){
							$('.form-message',form).html('<p class="section-title text-dark-teal large bold m-0 text-center">Resultados enviados</p>').show();
						} else {
							$('.form-message',form).html('<p class="section-title text-dark-teal large bold m-0 text-center">Your test results have been sent</p>').show();
						}
						$('.form-content',form).slideUp();
					},
					error: function(xhr, ajaxOptions, thrownError){
						$(form).addClass('error').removeClass('submitted');
						$('.submit',form).prop('disabled', false).removeClass('loading');
						$('.form-message',form).html('<div class="form-message-inner warning">There was a problem submitting the form. Please review your information and try again or try again later.</div>').show(); 
					}
				});

			}
		});

	}

});