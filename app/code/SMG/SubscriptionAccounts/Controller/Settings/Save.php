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
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
    ) {
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->_formKey = $formKey;
        $this->_encryptor = $encryptor;
        $this->_customerSession = $customerSession;
        $this->_storeManager = $storeManager;
        $this->_subscriptionHelper = $subscriptionHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        // Check form key
        if (! $this->formValidation($this->_request->getParam('form_key'))) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        $request = $this->_request->getParams();

        if( empty( $request['firstname'] ) || empty( $request['lastname'] ) ) {
            $this->_messageManager->addErrorMessage('First Name and Last Name cannot be empty.');

            $resultRedirect = $this->resultFactory->create( ResultFactory::TYPE_REDIRECT );
            $resultRedirect->setUrl( $this->_redirect->getRefererUrl() );
            return $resultRedirect;
        } else {
            // Update customer first and last name
            $customer = $this->getCustomer();
            $customer->setData( 'firstname', $request['firstname'] );
            $customer->setData( 'lastname', $request['lastname'] );
            $customer->save();

            $this->_messageManager->addSuccessMessage('Account updated.');
        }

        // Redirect back to the customer page with a success or error message
        $resultRedirect = $this->resultFactory->create( ResultFactory::TYPE_REDIRECT );
        $resultRedirect->setUrl( $this->_redirect->getRefererUrl() );

        return $resultRedirect;
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
