<?php
namespace SMG\SubscriptionApi\Controller\Adminhtml\Cancel;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\CancelHelper;
use SMG\SubscriptionApi\Model\RecurlySubscription;

/**
 * Class Index
 * @package SMG\SubscriptionApi\Controller\Adminhtml\Cancel
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \SMG\SubscriptionApi\Helper\RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var \SMG\SubscriptionApi\Helper\SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

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
     * @var LoggerInterface
     */
    protected $_logger;
    /**
     * @var RecurlySubscription
     */
    protected $_recurlySubscription;
    /**
     * @var CancelHelper
     */
    protected $_cancelHelper;

    /**
     * Index constructor.
     * @param \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
     * @param \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     */
    public function __construct(
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Data\Form\FormKey $formKey,
        RecurlySubscription $recurlySubscription,
        CancelHelper $cancelHelper,
        LoggerInterface $logger
    ) {
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->_formKey = $formKey;
        $this->_logger = $logger;
        $this->_recurlySubscription = $recurlySubscription;
        $this->_cancelHelper = $cancelHelper;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws NoSuchEntityException
     * @throws SecurityViolationException
     */
    public function execute()
    {
        // Check form key
        if (! $this->formValidation($this->_request->getParam('form_key'))) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        if (empty($this->_request->getParam('recurly_account_code'))) {
            // Redirect back to the customer page with a success or error message
            $error = 'No customer ID passed in.';
            $this->_logger->error($error);
            $this->_messageManager->addErrorMessage($error);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        }

        try {
            $accountCode = $this->_request->getParam('recurly_account_code');
            $this->_cancelHelper->cancelSubscriptions($accountCode,'','admin');
        } catch (Exception $e) {
            $error = 'Could not cancel Recurly subscriptions';
            $this->_logger->error($error . ' - ' . $e->getMessage());
            $this->_messageManager->addErrorMessage($error);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        }

        $this->_messageManager->addSuccessMessage('Subscriptions order cancel request is being processed.');

        // Redirect back to the customer page with a success or error message
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
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
        if ($this->_subscriptionHelper->useCsrf($this->_storeManager->getStore()->getId())) {
            return $this->_formKey->getFormKey() === $key;
        }

        return true;
    }
}
