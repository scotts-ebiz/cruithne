<?php
namespace SMG\SubscriptionApi\Block\Adminhtml\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Recurly_Client;
use Recurly_SubscriptionList;
use Recurly_NotFoundError;

class CustomerSubscriptions extends \Magento\Framework\View\Element\Template implements TabInterface
{

    protected $_coreRegistry;
    protected $_customer;
    protected $_helper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $helper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_customer = $customer;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Return customer id
     * 
     * @return string
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry( RegistryConstants::CURRENT_CUSTOMER_ID );
    }

    /**
     * Return customer's Recurly account code
     * 
     * @return string
     */
    public function getCustomerRecurlyAccountCode()
    {
        $customer = $this->_customer->load( $this->getCustomerId() );

        return $customer->getRecurlyAccountCode();
    }

    /**
     * Return active and future subscriptions of the customer
     * 
     * @return array
     * @throws Recurly_NotFoundError if Recurly account doesn't exist
     */
    public function getCustomerSubscriptions()
    {
        Recurly_Client::$apiKey = $this->_helper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_helper->getRecurlySubdomain();

        $currentSubscriptions = array();

        try {
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'active' ] );
            $futureSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'future' ] );

            foreach( $activeSubscriptions as $activeSubscription ) {
                array_push( $currentSubscriptions, $activeSubscription);
            }

            foreach( $futureSubscriptions as $futureSubscription ) {
                array_push( $currentSubscriptions, $futureSubscription);
            }

            return $currentSubscriptions;
        } catch (Recurly_NotFoundError $e) {
            print "Account Not Found: $e";
        }
    }

    public function getTabLabel()
    {
        return 'Scott\'s Subscriptions';
    }


    public function getTabTitle()
    {
        return 'Scott\'s Subscriptions';
    }


    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }
 
    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    public function getTabClass()
    {
        return '';
    }

    public function getTabUrl()
    {
        return $this->getUrl( 'customersubscriptions/*/customersubscriptions',[ '_current' => true ] );
    }

    public function isAjaxLoaded()
    {
        return true;
    }

}