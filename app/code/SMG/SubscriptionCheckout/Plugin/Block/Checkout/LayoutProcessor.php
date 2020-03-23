<?php
namespace SMG\SubscriptionCheckout\Plugin\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor as CheckoutLayoutProcessor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;

class LayoutProcessor
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var SubscriptionHelper
     */
    protected $_subscriptionHelper;

    public function __construct(
        StoreManagerInterface $storeManager,
        SubscriptionHelper $subscriptionHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->_subscriptionHelper = $subscriptionHelper;
    }

    /**
     * @param CheckoutLayoutProcessor $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterProcess(
        CheckoutLayoutProcessor $subject,
        $result
    ) {
        // We are not in the Lawn Subscription store, so return.
        if (! $this->_subscriptionHelper->isActive($this->_storeManager->getStore()->getId())) {
            return $result;
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'])) {
            foreach ($result['components']['checkout']['children']['steps']['children']['billing-step']
                     ['children']['payment']['children']['payments-list']['children'] as $key => $payment) {
                $subs = substr($key, 0, -4);

                $removeString = str_replace("-", "", $subs);

                /* Firstname */
                if (isset($payment['children']['form-fields']['children']['firstname'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['firstname']['validation'] = ['required-entry' => false,'required-entry-bfirstname' => true];
                }
                /* Lastname */
                if (isset($payment['children']['form-fields']['children']['lastname'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['lastname']['validation'] = ['required-entry' => false,'required-entry-blastname' => true];
                }
                /* Street */
                if (isset($payment['children']['form-fields']['children']['street'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['street']['children']['0']['validation'] = ['required-entry' => false,'required-entry-bstreet-0' => true];
                }

                if (isset($payment['children']['form-fields']['children']['street'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['street']['children']['1']['validation'] = ['required-entry-bstreet-1' => true];
                }

                if (isset($payment['children']['form-fields']['children']['street'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['street']['children']['2']['validation'] = ['required-entry-bstreet-2' => true];
                }
                /* City */
                if (isset($payment['children']['form-fields']['children']['postcode'])) {
                    $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['city']['validation'] = ['required-entry-bcity' => true];
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
