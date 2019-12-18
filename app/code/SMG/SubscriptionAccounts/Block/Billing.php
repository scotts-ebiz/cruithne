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
     * Subscriptions block constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \Magento\Directory\Block\Data $directoryData,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_directoryData = $directoryData;
        $this->_regionFactory = $regionFactory;
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
            
            return $billing_info;
        } catch (Recurly_NotFoundError $e) {
            print "Not found: $e";
        }
    }

    public function getStates()
    {
        return $this->_directoryData->getRegionCollection()->toOptionArray();
    }

    public function getCountries()
    {
        return $this->_directoryData->getCountryCollection()->toOptionArray();
    }

    public function getStateIdByCode( $region_code, $country_code )
    {
        $state = $this->_regionFactory->create()->loadByCode( $region_code, $country_code);
        return $state->getRegionId();
    }


}
