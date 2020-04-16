<?php

namespace SMG\SubscriptionAccounts\Controller\Billing;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Recurly_Client;
use Recurly_BillingInfo;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;

/**
 * Class Save
 * @package SMG\SubscriptionAccounts\Controller\Billing
 */
class Save extends Action
{

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var FormKey
     */
    protected $_formKey;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var JsonFactory
     */
    protected $_jsonResultFactory;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param FormKey $formKey
     * @param CustomerSession $customerSession
     * @param SubscriptionHelper $subscriptionHelper
     * @param Customer $customer
     * @param RecurlyHelper $recurlyHelper
     * @param StoreManagerInterface $storeManager
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ManagerInterface $messageManager,
        FormKey $formKey,
        CustomerSession $customerSession,
        SubscriptionHelper $subscriptionHelper,
        Customer $customer,
        RecurlyHelper $recurlyHelper,
        StoreManagerInterface $storeManager,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->_formKey = $formKey;
        $this->_customerSession = $customerSession;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_storeManager = $storeManager;
        $this->_jsonResultFactory = $jsonFactory;
        $this->_logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute
     * @return ResponseInterface|Json|ResultInterface
     * @throws NoSuchEntityException
     * @throws SecurityViolationException
     */
    public function execute()
    {
        $request = json_decode( $this->_request->getContent() );

        // Check form key
        if ( ! $this->formValidation( $request->form_key ) ) {
            $error = 'Unauthorized';
            $this->_logger->error($error);
            throw new SecurityViolationException( __($error) );
        }

        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $result = $this->_jsonResultFactory->create();

        try {
            $billing_info = new Recurly_BillingInfo();
            $billing_info->account_code = $this->getGigyaUid();
            $billing_info->token_id = $request->token;
            $billing_info->update();

            $data = array(
                'success'   => true,
                'message'   => 'Billing details updated.'
            );

            $result->setData( $data );
            return $result;
        } catch(\Exception $e ) {
            $this->_logger->error($e->getMessage());
            $data = array(
                'success'   => false,
                'message'   => $e->getMessage()
            );

            $result->setData( $data );
            return $result;
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
    private function getGigyaUid()
    {
        $customer = $this->_customer->load( $this->getCustomerId() );

        if( $customer->getGigyaUid() ) {
            return $customer->getGigyaUid();
        }

        return false;
    }

    /**
     * Test the form key for CSRF form validation
     *
     * @param $key
     * @return bool
     * @throws NoSuchEntityException
     */
    private function formValidation($key)
    {
        if ($this->_subscriptionHelper->useCsrf( $this->_storeManager->getStore()->getId() ) ) {
            return $this->_formKey->getFormKey() === $key;
        }

        return true;
    }

}