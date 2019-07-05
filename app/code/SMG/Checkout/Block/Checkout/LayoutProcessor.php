<?php
namespace SMG\Checkout\Block\Checkout;
class LayoutProcessor
{
    public function afterProcess(
    \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
    array $jsLayout
    ) {
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children']
            )) {

                foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                     ['payment']['children']['payments-list']['children'] as $key => $payment) {

						/* Firstname */
						if (isset($payment['children']['form-fields']['children']['firstname'])) {
							$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
							['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
							['firstname']['validation'] = ['required-entry' => false,'required-entry-bfirstname' => true];
						}
						
						/* Lastname */
						if (isset($payment['children']['form-fields']['children']['lastname'])) {
							$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
							['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
							['lastname']['validation'] = ['required-entry' => false,'required-entry-blastname' => true];
						}
						
						/* Postcode */
						if (isset($payment['children']['form-fields']['children']['postcode'])) {
							$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
							['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
							['postcode']['validation'] = ['required-entry-bpcode' => true,'validate-zip-us' => true];
						}
						
						/* Telephone */
						if (isset($payment['children']['form-fields']['children']['telephone'])) {
							$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
							['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
							['telephone']['validation'] = ['required-entry' => false,'required-entry-btelephone' => true];
						}
					}
                }
				return $jsLayout;
            }
    }