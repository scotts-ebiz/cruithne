define([
	'jquery',
	'jquery/ui',
	'jquery/validate',
	'mage/translate'
], function ($) {
	'use strict';

	return function (validator) {
		$('#coupon_code').keyup(function () {
			$('button.apply').prop('disabled', false);
		});




		// $("#discount-code").on('keyup change', function () {
		// 	alert('Keyup Alert');

		// 	// $('.actions-toolbar button').prop('disabled', true);

		// 	// if ($("input[name='discount_code_input']").val() != '') {
		// 	// 	$('.actions-toolbar button').prop('disabled', false);
		// 	// }
		// });



		validator.addRule(
			'discount-btn-status',
			function (value) {
				if ($("#discount-code").val() != '') {
					if (value.match(/^[a-zA-Z\.\-\' ]*$/)) {
						$("#payment-discount-apply").addClass('has-value');
					} else {
						$("#payment-discount-apply").addClass('do-something');
					}

				} else {
					$("#payment-discount-apply").addClass('has-no-value');
				}
			}
		);

		validator.addRule(
			'required-entry-firstname',
			function (value) {
				if ($("input[name='firstname']").val() != '') {
					if (!isNaN(value)) {
						return false;
					}
					if (value.match(/^[a-zA-Z\.\-\' ]*$/)) {
						return true
					} else {
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
				var inpt = 'billingAddress' + $(".payment-method._active input[type='radio']").val() + '.firstname';
				if ($("div[name='" + inpt + "'] input[name='firstname']").val() != '') {

					if (!isNaN(value)) {
						return false;
					}
					if (value.match(/^[a-zA-Z\.\-\' ]*$/)) {
						return true
					} else {
						return false;
					}

				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter your first name.')
		);
		validator.addRule(
			'required-entry-lastname',
			function (value) {
				if ($("input[name='lastname']").val() != '') {
					if (!isNaN(value)) {
						return false;
					}
					if (value.match(/^[a-zA-Z\.\-\' ]*$/)) {
						return true
					} else {
						return false;
					}
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter your last name.')
		);
		validator.addRule(
			'required-entry-blastname',
			function (value) {
				var inpt = 'billingAddress' + $(".payment-method._active input[type='radio']").val() + '.lastname';
				if ($("div[name='" + inpt + "'] input[name='lastname']").val() != '') {
					if (!isNaN(value)) {
						return false;
					}
					if (value.match(/^[a-zA-Z\.\-\' ]*$/)) {
						return true
					} else {
						return false;
					}
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter your last name.')
		);
		validator.addRule(
			'required-entry-bcity',
			function (value) {
				if ($(".billing-address-form input[name='city']").val() != '') {
					if (!isNaN(value)) {
						return false;
					}
					if (value.match(/^[a-zA-Z ]*$/)) {
						return true
					} else {
						return false;
					}
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter a valid city')
		);

		/* Start shipping and billing street address validation */
		validator.addRule(
			'required-entry-street-0',
			function (value) {
				if ($("input[name='street[0]']").val() != '') {
					if (!isNaN(value)) {
						return false;
					}
					if (/^\b(?!none\b)([a-zA-Z0-9()":;'-.]+ ?)+$/i.test(value)) {
						return true
					} else {
						return false;
					}

				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter a valid street address.')
		);

		validator.addRule(
			'required-entry-street-1',
			function (value) {
				if (/^\b(?!none\b)([a-zA-Z0-9()":;'-.]+ ?)+$/i.test(value) || value == '') {
					return true
				} else {
					return false;
				}
			},
			$.mage.__('Please only include apartment or suite number, if applicable.')
		);

		validator.addRule(
			'required-entry-street-2',
			function (value) {
				if (/^\b(?!none\b)([a-zA-Z0-9()":;'-.]+ ?)+$/i.test(value) || value == '') {
					return true
				} else {
					return false;
				}
			},
			$.mage.__('Please enter a valid street address.')
		);

		validator.addRule(
			'required-entry-bstreet-0',
			function (value) {
				var inpt = 'billingAddress' + $(".payment-method._active input[type='radio']").val() + '.street.0';
				if ($("div[name='" + inpt + "'] input[name='street[0]']").val() != '') {
					if (!isNaN(value)) {
						return false;
					}
					if (/^\b(?!none\b)([a-zA-Z0-9()":;'-.]+ ?)+$/i.test(value)) {
						return true
					} else {
						return false;
					}

				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter a valid street address.')
		);

		validator.addRule(
			'no-pobox-allowed',
			function (value) {
				var pattern = new RegExp('\\b[p]*(ost)*\\.*\\s*[o|0]*(ffice)*\\.*\\s*b[o|0]x\\b', 'i');
				return !pattern.test(value);
			},
			$.mage.__('We are unable to ship to to PO Boxes. Please enter a valid street address.')
		);

		validator.addRule(
			'required-entry-bstreet-1',
			function (value) {

				if (/^\b(?!none\b)([a-zA-Z0-9()":;'-.]+ ?)+$/i.test(value) || value == '') {
					return true
				} else {
					return false;
				}
			},
			$.mage.__('Please only include apartment or suite number, if applicable.')
		);

		validator.addRule(
			'required-entry-bstreet-2',
			function (value) {

				if (/^\b(?!none\b)([a-zA-Z0-9()":;'-.]+ ?)+$/i.test(value) || value == '') {
					return true
				} else {
					return false;
				}
			},
			$.mage.__('Please enter a valid street address.')
		);

		/* End street address validation */
		validator.addRule(
			'required-entry-regionid',
			function (value) {

				if ($("select[name='region_id']").val() != '') {
					return true;
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please select a State.')
		);
		validator.addRule(
			'required-entry-bregionid',
			function (value) {
				var inpt = 'billingAddress' + $(".payment-method._active input[type='radio']").val() + '.region_id';
				if ($("div[name='" + inpt + "'] select[name='region_id']").val() != '') {
					return true;
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please select a State.')
		);
		validator.addRule(
			'required-entry-pcode',
			function (value) {

				if ($("input[name='postcode']").val() != '') {
					if (value.length < 5) {
						return false;
					}
					return true;
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter a valid ZIP code.')
		);
		validator.addRule(
			'required-entry-exceptpost',
			function (value) {
				var zipcodesTemp = ['967', '968', '995', '996', '997', '998', '999'];
				var temp = value.substring(0, 3);
				if ($("input[name='postcode']").val() != '') {
					if ($.inArray(temp, zipcodesTemp) >= 0) {
						return false;
					}
					return true;
				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('We are unable to ship to Alaska and Hawaii at this time.')
		);
		validator.addRule(
			'required-entry-bpcode',
			function (value) {
				var inpt = 'billingAddress' + $(".payment-method._active input[type='radio']").val() + '.postcode';
				if ($("div[name='" + inpt + "'] input[name='postcode']").val() != '') {
					if (value.length < 5) {
						return false;
					}
					return true;
				}
			},
			$.mage.__('Please enter a valid ZIP code.')
		);
		validator.addRule(
			'required-entry-telephone',
			function (value) {
				if ($("input[name='telephone']").val() != '') {
					var filter = /^[(]?(\d{3})[)]?[-|\s]?(\d{3})[-|\s]?(\d{4})$/;
					if (value.length > 9 && filter.test(value)) {
						return true;
					} else {
						return false;
					}


				} else {
					return !$.mage.isEmpty(value);
				}
			},
			$.mage.__('Please enter a valid phone number.')
		);
		validator.addRule(
			'required-entry-btelephone',
			function (value) {
				var inpt = 'billingAddress' + $(".payment-method._active input[type='radio']").val() + '.telephone';
				if ($("div[name='" + inpt + "'] input[name='telephone']").val() != '') {
					var filter = /^[(]?(\d{3})[)]?[-|\s]?(\d{3})[-|\s]?(\d{4})$/;
					if (value.length > 9 && filter.test(value)) {
						return true;
					} else {
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
