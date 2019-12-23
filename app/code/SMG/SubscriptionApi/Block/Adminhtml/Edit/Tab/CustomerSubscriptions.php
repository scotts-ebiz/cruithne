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
    protected $_urlInterface;
    protected $_formKey;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $helper,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_customer = $customer;
        $this->_helper = $helper;
        $this->_urlInterface = $urlInterface;
        $this->_formKey = $formKey;
        parent::__construct($context, $data);
    }

    /**
     * Return form key
     * 
     * @return string
     */
    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
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
     * @return string|bool
     */
    public function getCustomerRecurlyAccountCode()
    {
        return '7df2ba4bb75745d0bf66a9c8337cfe60';

        $customer = $this->_customer->load( $this->getCustomerId() );

        if( $customer->getGigyaUid() ) {
            return $customer->getGigyaUid();
        }

        return false;
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

        // Create empty array so we can merge active and future subscriptions
        $subscriptions = array();

        // Store refund amount
        $totalAmount = false;

        try {
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'active' ] );
            $futureSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'future' ] );

            foreach( $activeSubscriptions as $subscription ) {
                array_push( $subscriptions, $subscription);
                $totalAmount += $subscription->unit_amount_in_cents;
            }

            foreach( $futureSubscriptions as $subscription ) {
                array_push( $subscriptions, $subscription);
                $totalAmount += $subscription->unit_amount_in_cents;
            }

            return array( 'success' => true, 'subscriptions' => $subscriptions, 'total_amount' => $this->convertAmountToDollars( $totalAmount ) );
        } catch (Recurly_NotFoundError $e) {
            return array( 'success' => false, 'error_message' => $e->getMessage() );
        }
    }

    /**
     * Convert cents to dollars
     *
     */
    public function convertAmountToDollars($amount)
    {
        return number_format(($amount/100), 2, '.', ' ');
    }

    public function getCancelUrl()
    {
        echo $this->_urlInterface->getUrl('customersubscriptions/cancel/index');
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