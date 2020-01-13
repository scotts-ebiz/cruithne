<?php

namespace SMG\SubscriptionAccounts\Block;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Block\Data;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Recurly_BillingInfo;
use Recurly_Client;
use SMG\SubscriptionApi\Helper\RecurlyHelper;

/**
 * Class Billing
 * @package SMG\SubscriptionAccounts\Block
 */
class Billing extends Template
{
    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var Data
     */
    protected $_directoryData;

    /**
     * @var RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Subscriptions block constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param Customer $customer
     * @param RecurlyHelper $recurlyHelper
     * @param Data $directoryData
     * @param RegionFactory $regionFactory
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        Customer $customer,
        RecurlyHelper $recurlyHelper,
        Data $directoryData,
        RegionFactory $regionFactory,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_directoryData = $directoryData;
        $this->_regionFactory = $regionFactory;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Return customer id
     *
     * @return string
     */
    private function getCustomerId()
    {
        return $this->_customerSession->getCustomer()->getId();
    }

    /**
     * Return customer's Recurly account code
     *
     * @return string|bool
     */
    private function getCustomerRecurlyAccountCode()
    {
        $customer = $this->_customer->load($this->getCustomerId());

        if ($customer->getRecurlyAccountCode()) {
            return $customer->getRecurlyAccountCode();
        }

        return false;
    }

    /**
     * Return customer's billing information
     *
     * @return array
     */
    public function getBillingInformation()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $billing = [];

        try {
            $billing_info = Recurly_BillingInfo::get($this->getCustomerRecurlyAccountCode());

            $billing['first_name'] = $billing_info->first_name;
            $billing['last_name'] = $billing_info->last_name;
            $billing['address1'] = $billing_info->address1;
            $billing['address2'] = $billing_info->address2;
            $billing['city'] = $billing_info->city;
            $billing['country'] = $billing_info->country;
            $billing['state'] = $billing_info->state;
            $billing['zip'] = $billing_info->zip;
        } catch (\Exception $e) {
            // Not truly an error state. We expect this for users without recurly accounts
            $billing['first_name'] = '';
            $billing['last_name'] = '';
            $billing['address1'] = '';
            $billing['address2'] = '';
            $billing['city'] = '';
            $billing['country'] = '';
            $billing['state'] = '';
            $billing['zip'] = '';
        }

        return $billing;
    }

    /**
    * Get the form action URL for POST the save request
    *
    * @return string
    */
    public function saveFormAction()
    {
        return '/account/billing/save';
    }

    /**
     * Return states
     *
     * @return array
     */
    public function getStates()
    {
        $states = $this->_directoryData->getRegionCollection()->toOptionArray();
        $statesArray = [];

        foreach ($states as $key => $state) {
            if (! is_object($state['label'])) {
                $statesArray[$key]['value'] = $this->getRegionCodeByName($state['label'])['code'];
            } else {
                $statesArray[$key]['value'] = '';
            }
            $statesArray[$key]['text'] = $state['label'];
        }

        return $statesArray;
    }

    /**
     * Return countries
     *
     * @return array
     */
    public function getCountries()
    {
        return $this->_directoryData->getCountryCollection()->toOptionArray();
    }

    /**
     * Return state details (region_id, country_id, code, name, ...) by state name
     *
     * @param $region
     * @return array
     */
    public function getRegionCodeByName($region)
    {
        return $this->_collectionFactory->create()->addRegionNameFilter($region)->getFirstItem()->toArray();
    }
}
