<?php
namespace SMG\SubscriptionAccounts\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Link extends \Magento\Framework\View\Element\Html\Link\Current {

    /**
     * @var DefaultPathInterface
     */
    protected $_defaultPath;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;
    
	/**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;
	
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        DefaultPathInterface $defaultPath,
	CustomerRepositoryInterface $customerRepositoryInterface,
        array $data = []
    )
    {
        $this->_customerSession = $customerSession;
	$this->_customerRepositoryInterface = $customerRepositoryInterface;
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
        $customer = $this->_customerRepositoryInterface->getById( $this->getCustomerId() );
        if(!empty($customer->getCustomAttribute('gigya_uid')->getValue())) {
            return true;
        }

        return false;
    }
}
