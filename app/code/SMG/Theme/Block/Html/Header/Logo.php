<?php

namespace SMG\Theme\Block\Html\Header;

use SMG\SubscriptionApi\Model\RecurlySubscription;
use Recurly_Client;

/**
 * Logo page header block
 *
 * @api
 * @since 100.0.2
 */
class Logo extends \Magento\Theme\Block\Html\Header\Logo
{

    /** @var  */
    protected $_session;

    /**
     * @var RecurlySubscription
     */
    protected $_recurlySubscription;

    /**
     * @var \SMG\SubscriptionApi\Helper\RecurlyHelper
     */
    protected $_recurlyHelper;

     /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;

    /**
     * Logo constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper
     * @param \Magento\Customer\Model\Session $session
     * @param \SMG\SubscriptionApi\Model\RecurlySubscription $recurlySubscription
     * @param \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper,
        \Magento\Customer\Model\Session $session,
        RecurlySubscription $recurlySubscription,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \Magento\Customer\Model\Customer $customer,
        array $data = []
    ) {
        $this->_session = $session;
        $this->_recurlySubscription = $recurlySubscription;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_customer = $customer;
        parent::__construct($context, $fileStorageHelper, $data);
    }

    /**
     * Is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        $visitorData = $this->_session->getData("visitor_data");

        return isset( $visitorData["do_customer_login"] ) && $visitorData["do_customer_login"];
    }

    /**
     * Check if customer has subscriptions
     * 
     * @return bool
     */
    public function hasSubscriptions()
    {
        if( $this->isLoggedIn() ) {
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

            $visitorData = $this->_session->getData("visitor_data");
            $customer = $this->_customer->load( $visitorData['customer_id'] );

            $subscriptions = $this->_recurlySubscription->hasRecurlySubscription( $customer->getGigyaUid() );

            return ( $subscriptions['has_subscriptions'] === true ) ? true : false;
        }

        return false;
    }
}