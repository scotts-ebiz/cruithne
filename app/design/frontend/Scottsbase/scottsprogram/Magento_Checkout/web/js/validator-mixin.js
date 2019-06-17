define([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate'
], function($){
'use strict';

	return function (validator) {
 
		validator.addRule(
			'required-entry-firstname',
			function (value) {
				if ($("input[name='firstname']").val() != '') {
					if (!isNaN(value)) {
						return false;
					}
					if( value.match( /^[a-zA-Z ]*$/) ) {
						 return true
					}else{
						return false;
					}
					
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter valid name.')
		);
		validator.addRule(
			'required-entry-lastname',
			function (value) {
				if ($("input[name='lastname']").val() != '') {
					if (!isNaN(value)) {
						return false;
					}
					if( value.match( /^[a-zA-Z ]*$/) ) {
						 return true
					}else{
						return false;
					}
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter valid name.')
		);
		validator.addRule(
			'required-entry-pcode',
			function (value) {
				
				if ($("input[name='postcode']").val() != '') {
					if(value.length < 5){
						$('#shipping-method-buttons-container button').prop('disabled', true);
						return false;
					}
					$('#shipping-method-buttons-container button').prop('disabled', false);
					return true;
				} else {
					$('#shipping-method-buttons-container button').prop('disabled', true);
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter zip code.')
		);
		validator.addRule(
			'required-entry-telephone',
			function (value) {
				if ($("input[name='telephone']").val() != '') {
					var str = $("input[name='telephone']").val();
					if(str){
						var mob = str.replace(/\-/g, '');
						if(mob.length < 10){
							return false;
						}
					}
					var filter = /^((\+[1-9]{1,4}[ \-]*)|(\([0-9]{2,3}\)[ \-]*)|([0-9]{2,4})[ \-]*)*?[0-9]{3,4}?[ \-]*[0-9]{3,4}?$/;
					if (value.length > 9 && filter.test(value)) {
						return true;
					}else {
						return false;
					}
					

				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter valid phone.')
		);
		
		return validator;
	};
});