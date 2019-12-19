<?php
namespace SMG\SubscriptionAccounts\Block;

use Recurly_Client;
use Recurly_NotFoundError;
use Recurly_BillingInfo;

class Billing extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;

    /**
     * @var \SMG\SubscriptionApi\Helper\RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var \Magento\Directory\Block\Data
     */
    protected $_directoryData;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Subscriptions block constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Directory\Model\ResourceModel\Region\Collection $collection
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \Magento\Directory\Block\Data $directoryData,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $collectionFactory,
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
        $customer = $this->_customer->load( $this->getCustomerId() );

        if( $customer->getRecurlyAccountCode() ) {
            return $customer->getRecurlyAccountCode();
        }

        return false;
    }

    /**
     * Return customer's billing information
     * 
     * @return object $billing_info
     */
    public function getBillingInformation()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        try {
            $billing_info = Recurly_BillingInfo::get($this->getCustomerRecurlyAccountCode());

            $billing = array();
            $billing['first_name'] = $billing_info->first_name;
            $billing['last_name'] = $billing_info->last_name;
            $billing['address1'] = $billing_info->address1;
            $billing['address2'] = $billing_info->address2;
            $billing['city'] = $billing_info->city;
            $billing['country'] = $billing_info->country;
            $billing['state'] = $billing_info->state;
            $billing['zip'] = $billing_info->zip;
            
            return $billing;
        } catch (Recurly_NotFoundError $e) {
            print "Not found: $e";
        }
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
        $statesArray = array();

        foreach( $states as $key => $state ) {
            if( ! is_object( $state['label'] ) ) {
                $statesArray[$key]['value'] = $this->getRegionCodeByName( $state['label'] )['code'];

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
     * @return array
     */
    public function getRegionCodeByName( $region )
    {
        $regionCode = $this->_collectionFactory->create()->addRegionNameFilter( $region )->getFirstItem()->toArray();

        return $regionCode;
    }

}
