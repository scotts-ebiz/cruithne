<?php

namespace SMG\SubscriptionAccounts\Controller\Billing;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Recurly_Client;
use Recurly_NotFoundError;
use Recurly_BillingInfo;

class Save extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $_formKey;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \SMG\SubscriptionApi\Helper\SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;

    /**
     * @var \SMG\SubscriptionApi\Helper\RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Save constructor.
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
     * @param \Magento\Customer\Model\Customer $customer
     * @param \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Customer\Model\Session $customerSession,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->_formKey = $formKey;
        $this->_customerSession = $customerSession;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        // Check form key
        if ( ! $this->formValidation( $this->_request->getParam( 'form_key' ) ) ) {
            throw new SecurityViolationException( __( 'Unauthorized' ) );
        }

        // Get form data
        $request = $this->_request->getParams();

        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $resultRedirect = $this->resultFactory->create( ResultFactory::TYPE_REDIRECT );
        $resultRedirect->setUrl( $this->_redirect->getRefererUrl() );

        try {
            $billing_info = new Recurly_BillingInfo();
            $billing_info->account_code = $this->getCustomerRecurlyAccountCode();
            $billing_info->token_id = $request['recurly-token'];
            $billing_info->update();

            $this->_messageManager->addSuccessMessage( 'Billing informations updated' );

            return $resultRedirect;
        } catch( Recurly_NotFoundError $e ) {
            $this->_messageManager->addErrorMessage( $e->getMessage() );

            return $resultRedirect;
        }
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
     * Test the form key for CSRF form validation
     *
     * @param $key
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function formValidation($key)
    {
        if ($this->_subscriptionHelper->useCsrf( $this->_storeManager->getStore()->getId() ) ) {
            return $this->_formKey->getFormKey() === $key;
        }

        return true;
    }
}
