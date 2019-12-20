<?php

namespace SMG\SubscriptionAccounts\Controller\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Recurly_Client;
use Recurly_Account;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;

/**
 * Class Save
 * @package SMG\SubscriptionAccounts\Controller\Settings
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
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var AccountManagement
     */
    protected $_accountManagement;

    /**
     * @var JsonFactory
     */
    protected $_jsonResultFactory;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;
    
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param FormKey $formKey
     * @param Encryptor $encryptor
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param SubscriptionHelper $subscriptionHelper
     * @param ObjectManagerInterface $objectManager
     * @param AccountManagement $accountManagement
     * @param JsonFactory $jsonFactory
     * @param Customer $customer
     * @param RecurlyHelper $recurlyHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ManagerInterface $messageManager,
        FormKey $formKey,
        Encryptor $encryptor,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        SubscriptionHelper $subscriptionHelper,
        ObjectManagerInterface $objectManager,
        AccountManagement $accountManagement,
        JsonFactory $jsonFactory,
        Customer $customer,
        RecurlyHelper $recurlyHelper,
        LoggerInterface $logger
    ) {
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->_formKey = $formKey;
        $this->_encryptor = $encryptor;
        $this->_customerSession = $customerSession;
        $this->_storeManager = $storeManager;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_objectManager = $objectManager;
        $this->_accountManagement = $accountManagement;
        $this->_jsonResultFactory = $jsonFactory;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute
     * @return ResponseInterface|Json|ResultInterface
     * @throws SecurityViolationException
     * @throws LocalizedException
     * @throws NoSuchEntityException
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

        $result = $this->_jsonResultFactory->create();

        // Get current customer
        $customer = $this->getCustomer();

        if( ! empty( $request->firstname ) && ! empty( $request->lastname ) && ! empty( $request->email ) ) {
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

            try {
                $customer->setData( 'firstname', $request->firstname );
                $customer->setData( 'lastname', $request->lastname );
                $customer->setData( 'email', $request->email );
                $customer->save();

                $account = Recurly_Account::get( $this->getGigyaUid() );
                $account->email = $request->email;
                $account->first_name = $request->firstname;
                $account->last_name = $request->lastname;
                $account->update();
            } catch(\Exception $e) {
                $error = 'There was a problem with updating the account details ( '. $e->getMessage() .' )';
                $this->_logger->error($error);
                $data = array(
                    'success'   => false,
                    'message'   => $error
                );
                $result->setData( $data );
                return $result;
            }
        } else {
            $data = array(
                'success'   => false,
                'message'   => 'First name, last name or email is missing.'
            );
            $result->setData( $data );
            return $result;
        }

        $isPasswordChanged = false;

        // If current password is not empty
        if( ! empty( $request->current_password ) ) {
            if( ! empty( $request->new_password ) && ! empty( $request->confirm_new_password ) ) {
                if( $request->new_password != $request->confirm_new_password ) {
                    $data = array(
                        'success'   => false,
                        'message'   => 'New password do not match.'
                    );
                    $result->setData( $data );
                    return $result;
                }
                $isPasswordChanged = $this->_accountManagement->changePassword( $customer->getEmail(), $request->current_password, $request->new_password );
            }
        }

        if( $isPasswordChanged == true ) {
            $data = array(
                'success'   => true,
                'message'   => 'Account details and password updated.',
            );
            $result->setData( $data );
            return $result;
        } else {
            $data = array(
                'success'   => true,
                'message'   => 'Account details updated'
            );
            $result->setData( $data );
            return $result;
        }

    }

     /**
     * Return customer data to use it in the frontend form
     * 
     */
    private function getCustomer()
    {
        return $this->_customerSession->getCustomer();
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
     * Return Gigya Uid / customer's Recurly account code
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
