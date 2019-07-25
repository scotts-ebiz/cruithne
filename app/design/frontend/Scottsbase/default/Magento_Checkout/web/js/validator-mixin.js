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
					if( value.match( /^[a-zA-Z\. ]*$/) ) {
						 return true
					}else{
						return false;
					}
					
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter your first name.')
		);
		validator.addRule(
			'required-entry-bfirstname',
			function (value) {
				var inpt = 'billingAddress'+$(".payment-method._active input[type='radio']").val()+'.firstname';
				if ($("div[name='"+inpt+"'] input[name='firstname']").val() != '') {

					if (!isNaN(value)) {
						return false;
					}
					if( value.match( /^[a-zA-Z\. ]*$/) ) {
						 return true
					}else{
						return false;
					}
					
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter your first name')
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
			$.mage.__('Please enter a valid name.')
		);
		validator.addRule(
			'required-entry-blastname',
			function (value) {
				var inpt = 'billingAddress'+$(".payment-method._active input[type='radio']").val()+'.lastname';
				if ($("div[name='"+inpt+"'] input[name='lastname']").val() != '') {
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
			$.mage.__('Please enter your last name')
		);
		validator.addRule(
			'required-entry-bcity',
			function (value) {
				if ($(".billing-address-form input[name='city']").val() != '') {
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
			$.mage.__('Please enter a valid city')
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
			$.mage.__('Please enter a valid zip code.')
		);
		validator.addRule(
			'required-entry-bpcode',
			function (value) {
				var inpt = 'billingAddress'+$(".payment-method._active input[type='radio']").val()+'.postcode';
				if ($("div[name='"+inpt+"'] input[name='postcode']").val() != '') {
					if(value.length < 5){
						return false;
					}
					return true;
				} 
			},
			$.mage.__('Please enter a valid zip code.')
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
					var filter = /\d{3}-\d{3}-\d{4}$/;
					if (value.length > 9 && filter.test(value)) {
						return true;
					}else {
						return false;
					}
					

				} else { 
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter a valid phone.')
		);
		validator.addRule(
			'required-entry-btelephone',
			function (value) {
				var inpt = 'billingAddress'+$(".payment-method._active input[type='radio']").val()+'.telephone';
				if ($("div[name='"+inpt+"'] input[name='telephone']").val() != '') {
					var str = $("div[name='"+inpt+"'] input[name='telephone']").val();
					if(str){
						var mob = str.replace(/\-/g, '');
						if(mob.length < 10){
							return false;
						}
					}
					var filter = /\d{3}-\d{3}-\d{4}$/;
					if (value.length > 9 && filter.test(value)) {
						return true;
					}else {
						return false;
					}
					

				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter a valid phone number.')
		);
		
		return validator;
	};
});