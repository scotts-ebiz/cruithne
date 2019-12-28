<?php
namespace SMG\SubscriptionApi\Controller\Adminhtml\Cancel;

use Magento\Framework\Controller\ResultFactory;
use \SMG\SubscriptionApi\Model\RecurlySubscription as RecurlySubscriptionModel;
use \SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionModel;
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

    /** @var RecurlySubscriptionModel  */
    protected $_recurlySubscriptionModel;

    /** @var SubscriptionModel */
    private $_subscriptionModel;

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
        RecurlySubscriptionModel $recurlySubscriptionModel,
        SubscriptionModel $subscriptionModel
    ) {
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->_formKey = $formKey;
        $this->_recurlySubscriptionModel = $recurlySubscriptionModel;
        $this->_subscriptionModel = $subscriptionModel;
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

        try {
            // Get customer's Recurly account code
            $account_code = $this->_request->getParam( 'recurly_account_code' );

            // Cancel recurly subscriptions
            $cancelledSubscriptionIds = $this->_recurlySubscriptionModel->cancelRecurlySubscriptions( true, true, $account_code );

            // Find the master subscription id
            $masterSubscriptionId = null;
            foreach ( $cancelledSubscriptionIds as $planCode => $cancelledSubscriptionId ) {
                if ( in_array( $planCode, ['annual', 'seasonal']) ) {
                    $masterSubscriptionId = $cancelledSubscriptionId;
                }
            }
            if ( is_null( $masterSubscriptionId ) ) {
                throw new LocalizedException( __("Couldn't find the master subscription id.") );
            }

            // Find the subscription
            /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
            $subscription = $this->_subscriptionModel->getSubscriptionByMasterSubscriptionId( $masterSubscriptionId );

            // Cancel subscription orders
            $subscription->cancelSubscriptions( $this->_recurlySubscriptionModel );

            $this->_messageManager->addSuccessMessage('Subscription canceled.');
        } catch( \Exception $e) {
            $this->_messageManager->addErrorMessage('Subscriptions not canceled (' . $e->getMessage() . ')');
        }

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
