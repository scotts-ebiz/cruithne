<?php
namespace SMG\Checkout\Plugin\Block\Checkout;
class LayoutProcessor {
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, array $result) {
		    if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['street'])) {

		        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children']['0']
            ['validation'] = ['required-entry' => false,'required-entry-street-0' => true];

		        /* Street Address 0 - Label Change */
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children']['0']
            ['label'] = __('Street Address');

		        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children']['1']
            ['validation'] = ['required-entry-street-1' => true];

            /* Street Address 1 - Label Change */
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children']['1']
            ['label'] = __('Apartment/Suite/Other (Optional)');

            /* State/Provision label change*/
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']
            ['label'] = __('State');

            /* Shipping address State validation change*/
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']
            ['validation'] = ['required-entry-regionid' => true,'required-entry' => false];

            /* zip label change*/
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']['postcode']
            ['label'] = __('ZIP Code');
		    }
        if (isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'])) {
            foreach ($result['components']['checkout']['children']['steps']['children']['billing-step']
                     ['children']['payment']['children']['payments-list']['children'] as $key => $payment) {
                $subs = substr($key, 0, -4);

                $removeString = str_replace("-", "", $subs);

                /* Payment - Firstname */
                if (isset($payment['children']['form-fields']['children']['firstname'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['firstname']['validation'] = ['required-entry' => false,'required-entry-bfirstname' => true];
                }
                /* Payment - Lastname */
                if (isset($payment['children']['form-fields']['children']['lastname'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['lastname']['validation'] = ['required-entry' => false,'required-entry-blastname' => true];
                }
                /* Payment - Street */
                if (isset($payment['children']['form-fields']['children']['street'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['street']['children']['0']['validation'] = ['required-entry' => false,'required-entry-bstreet-0' => true];
                }

                /* Payment - Street Address 0 - Label Change */
                if (isset($payment['children']['form-fields']['children']['street'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['street']['children']['0']['label'] = __('Street Address');
                }

                if (isset($payment['children']['form-fields']['children']['street'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['street']['children']['1']['validation'] = ['required-entry-bstreet-1' => true];
                }

                /* Payment - Street Address 1 - Label Change */
                if (isset($payment['children']['form-fields']['children']['street'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['street']['children']['1']['label'] = __('Apartment/Suite/Other (Optional)');
                }

                if (isset($payment['children']['form-fields']['children']['street'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['street']['children']['2']['validation'] = ['required-entry-bstreet-2' => true];
                }
                /* Postcode */
                if (isset($payment['children']['form-fields']['children']['postcode'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['postcode']['validation'] = ['required-entry-bpcode' => true,'validate-zip-us' => true];
                }
                /* Telephone */
                if (isset($payment['children']['form-fields']['children']['telephone'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['telephone']['validation'] = ['required-entry' => false,'required-entry-btelephone' => true];
                }

                /* Remove Telephone Tooltip */
                if (isset($payment['children']['form-fields']['children']['telephone'])) {
                    unset(
                        $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                        ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                        ['telephone']['config']['tooltip']);
                }
                /* State/Provision label change*/
                if (isset($payment['children']['form-fields']['children']['region_id'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['region_id']['label'] = __('State');
                }
                /* ZIP label change*/
                if (isset($payment['children']['form-fields']['children']['postcode'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['postcode']['label'] = __('ZIP Code');
                }
            }
        }
        return $result;
    }
}