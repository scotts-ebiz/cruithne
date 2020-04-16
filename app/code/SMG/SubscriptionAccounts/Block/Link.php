<?php
namespace SMG\SubscriptionAccounts\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;

class Link extends \Magento\Framework\View\Element\Html\Link\Current {

    /**
     * @var DefaultPathInterface
     */
    protected $_defaultPath;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    public function __construct(
        Context $context,
        Customer $customer,
        CustomerSession $customerSession,
        DefaultPathInterface $defaultPath,
        array $data = []
    )
    {
        $this->_customer = $customer;
        $this->_customerSession = $customerSession;
        parent::__construct( $context, $defaultPath );
    }

    /**
     * Display "Billing Information" menu in My Account only if the customer has a Recurly account
     * 
     * @return string
     */
    public function toHtml()
    {
        if ( $this->hasRecurlyAccount() ) {
            return parent::toHtml();
        }

        return '';
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
     * Check if customer has a Recurly account
     *
     * @return bool
     */
    private function hasRecurlyAccount()
    {
        $customer = $this->_customer->load( $this->getCustomerId() );

        if( $customer->getRecurlyAccountCode() ) {
            return true;
        }

        return false;
    }
}