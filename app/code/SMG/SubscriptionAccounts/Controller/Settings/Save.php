<?php

namespace SMG\SubscriptionAccounts\Controller\Settings;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;

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
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \SMG\SubscriptionApi\Helper\SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Customer\Model\AccountManagement
     */
    protected $_accountManagement;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_jsonResultFactory;

    /**
     * Save constructor.
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Customer\Model\AccountManagement $accountManagement
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\AccountManagement $accountManagement,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
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
        parent::__construct($context);
    }

    public function execute()
    {        
        $request = json_decode( $this->_request->getContent() );

        // Check form key
        if ( ! $this->formValidation( $request->form_key ) ) {
            throw new SecurityViolationException( __( 'Unauthorized' ) );
        }

        $result = $this->_jsonResultFactory->create();

        // Get current customer
        $customer = $this->getCustomer();

        if( ! empty( $request->firstname ) && ! empty( $request->lastname ) && ! empty( $request->email ) ) {
            $customer->setData( 'firstname', $request->firstname );
            $customer->setData( 'lastname', $request->lastname );
            $customer->setData( 'email', $request->email );
            $customer->save();
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
