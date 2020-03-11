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
use Gigya\GigyaIM\Helper\GigyaMageHelper;

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
     * @var GigyaMageHelper
     */
    protected $_gigyaMageHelper;

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
     * @param GigyaMageHelper $gigyaMageHelper
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
        LoggerInterface $logger,
        GigyaMageHelper $gigyaMageHelper
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
        $this->_gigyaMageHelper = $gigyaMageHelper;
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

        // Validate first name
        if (empty($request->firstname)) {
            $error = 'First name is required';
            $this->_logger->error($error);
            $data = array(
                'success'   => false,
                'message'   => $error
            );
            $result->setData( $data );
            return $result;
        }

        // Validate last name
        if (empty($request->lastname)) {
            $error = 'Last name is required';
            $this->_logger->error($error);
            $data = array(
                'success'   => false,
                'message'   => $error
            );
            $result->setData( $data );
            return $result;
        }

        // Validate email name
        if (empty($request->email)) {
            $error = 'Email is required';
            $this->_logger->error($error);
            $data = array(
                'success'   => false,
                'message'   => $error
            );
            $result->setData( $data );
            return $result;
        }

        try {

            // Grab existing customer data in case Gigya fails to update and a rollback is needed.
            $currentEmail = $customer->getData('email');
            $currentFirstName = $customer->getData('firstname');
            $currentLastName = $customer->getData('lastname');

            // Update Magento customer data
            $customer->setData( 'firstname', $request->firstname );
            $customer->setData( 'lastname', $request->lastname );
            $customer->setData( 'email', $request->email );
            $customer->save();

            // Update Gigya customer data
            $gigyaData['profile']['firstName'] = $request->firstname;
            $gigyaData['profile']['lastName'] = $request->lastname;
            $gigyaData['profile']['email'] = $request->email;
            $this->_gigyaMageHelper->updateGigyaAccount( $this->getGigyaUid(), $gigyaData );
        } catch(\Exception $e) {

            // Rollback M2 db update since Gigya failed to update.
            $customer->setData( 'firstname', $currentFirstName );
            $customer->setData( 'lastname', $currentLastName );
            $customer->setData( 'email', $currentEmail );
            $customer->save();

            $error = 'There was a problem with updating the account details ( '. $e->getMessage() .' )';
            $this->_logger->error($error);
            $data = array(
                'success'   => false,
                'message'   => $error
            );
            $result->setData( $data );
            return $result;
        }

        // Update Recurly customer data
        try {
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();
            $account = Recurly_Account::get($this->getGigyaUid());
            $account->email = $request->email;
            $account->first_name = $request->firstname;
            $account->last_name = $request->lastname;
            $account->update();
        } catch (\Exception $e) {

            // It's ok if there isn't a Recurly account, if there is, there was a problem
            if ($e->getMessage() != 'Couldn\'t find Account') {
                $error = 'There was a problem with updating the account details ( ' . $e->getMessage() . ' )';
                $this->_logger->error($error);
                $data = array(
                    'success' => false,
                    'message' => $error
                );
                $result->setData($data);
                return $result;
            }
        }

        $isPasswordChanged = false;

        // If current password is not empty, let's update the passwords
        if( ! empty( $request->password ) || ! empty( $request->newPassword) || ! empty( $request->passwordRetype ) ) {
            if( ! empty( $request->newPassword ) && ! empty( $request->passwordRetype ) ) {

                // Make sure the new passwords match
                if( $request->newPassword != $request->passwordRetype ) {
                    $data = array(
                        'success'   => false,
                        'message'   => 'New password do not match.'
                    );
                    $result->setData( $data );
                    return $result;
                }

                try {
                    // Update Gigya password
                    $gigyaPasswordData['password'] = $request->password;
                    $gigyaPasswordData['newPassword'] = $request->newPassword;
                    $this->_gigyaMageHelper->updateGigyaAccount( $this->getGigyaUid(), $gigyaPasswordData);
                    $isPasswordChanged = true;
                } catch(\Exception $e) {
                    $error = 'There was a problem with updating the password ( ' . $e->getMessage() . ')';
                    $this->_logger->error( $error );
                    $data = array(
                        'success'   => false,
                        'message'   => $error,
                    );
                    $result->setData($data);
                    return $result;
                }
            } else {
                $error = 'There was a problem with updating the password.';
                $this->_logger->error( $error );
                $data = array(
                    'success'   => false,
                    'message'   => $error,
                );
                $result->setData($data);
                return $result;
            }
        }

        // Success states
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
