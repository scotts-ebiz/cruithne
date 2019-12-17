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

    protected $_objectManager;

    protected $_accountManagement;

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
        \Magento\Customer\Model\AccountManagement $accountManagement
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

        $resultRedirect = $this->resultFactory->create( ResultFactory::TYPE_REDIRECT );
        $resultRedirect->setUrl( $this->_redirect->getRefererUrl() );

        // Get current customer
        $customer = $this->getCustomer();

        if( ! empty( $request['firstname'] ) && ! empty( $request['lastname'] ) && ! empty( $request['email'] ) ) {
            $customer->setData( 'firstname', $request['firstname'] );
            $customer->setData( 'lastname', $request['lastname'] );
            $customer->setData( 'email', $request['email'] );
            $customer->save();
        } else {
            $this->_messageManager->addErrorMessage( 'Account not updated. First Name, Last Name or Email is missing.' );

            return $resultRedirect;
        }

        $isPasswordChanged = false;

        // If current password is not empty
        if( ! empty( $request['current_password'] ) ) {
            if( ! empty( $request['new_password'] ) && ! empty( $request['confirm_new_password'] ) ) {
                
                if( $request['new_password'] != $request['confirm_new_password'] ) {
                    $this->_messageManager->addErrorMessage( 'New passwords do not match.' );
                    return $resultRedirect;
                }

                $isPasswordChanged = $this->_accountManagement->changePassword( $customer->getEmail(), $request['current_password'], $request['new_password'] );

            } else {
                $this->_messageManager->addErrorMessage( 'New passwords missing.' );
                return $resultRedirect;
            }
        }

        if( $isPasswordChanged == true ) {
            $this->_messageManager->addSuccessMessage( 'Account details and password updated.' );
        } else {
            $this->_messageManager->addSuccessMessage( 'Account details updated.' );
        }

        return $resultRedirect;
    }

    private function getCurrentPasswordHash($customerId){
        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $sql = "Select password_hash from customer_entity WHERE entity_id = ".$customerId;
        $hash = $connection->fetchOne($sql);
        return $hash;
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
