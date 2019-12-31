<?php
namespace SMG\SubscriptionApi\Controller\Adminhtml\Cancel;

use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Recurly_Client;
use Recurly_Error;
use Recurly_NotFoundError;
use Recurly_Subscription;
use Recurly_SubscriptionList;

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
        LoggerInterface $logger
    ) {
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->_formKey = $formKey;
        $this->_logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws Recurly_Error
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {

        // Check form key
        if (! $this->formValidation($this->_request->getParam('form_key'))) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Check if subscription id, type and Recurly account code are in the request
        if (! empty($this->_request->getParam('recurly_account_code'))) {
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

            $recurlyAccountCode = $this->_request->getParam('recurly_account_code');

            try {
                $active_subscriptions = Recurly_SubscriptionList::getForAccount($recurlyAccountCode, [ 'state' => 'active' ]);
                $future_subscriptions = Recurly_SubscriptionList::getForAccount($recurlyAccountCode, [ 'state' => 'future' ]);

                foreach ($active_subscriptions as $subscription) {
                    $_subscription = Recurly_Subscription::get($subscription->uuid);
                    $_subscription->cancel();
                }

                foreach ($future_subscriptions as $subscription) {
                    $_subscription = Recurly_Subscription::get($subscription->uuid);
                    $_subscription->cancel();
                }

                $this->_messageManager->addSuccessMessage('Subscriptions cancelled.');
            } catch (Recurly_NotFoundError $e) {
                $error = 'There was an error with the cancellation. (' . $e->getMessage() . ')';
                $this->_logger->error($error);
                $this->_messageManager->addErrorMessage($error);
            }
        } else {
            $error = 'There was an error with the cancellation.';
            $this->_logger->error($error);
            $this->_messageManager->addErrorMessage($error);
        }

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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function formValidation($key)
    {
        if ($this->_subscriptionHelper->useCsrf($this->_storeManager->getStore()->getId())) {
            return $this->_formKey->getFormKey() === $key;
        }

        return true;
    }
}
