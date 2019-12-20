<?php

namespace SMG\SubscriptionAccounts\Plugin\Controller\Account;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;

/**
 * Class Index
 * @package SMG\SubscriptionAccounts\Plugin\Controller\Account
 */
class Index
{
    /**
     * @var ResultFactory
     */
    protected $_resultRedirect;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var SubscriptionHelper
     */
    private $_subscriptionHelper;

    /**
     * @var StoreManager
     */
    private $_storeManager;

    /**
     * @param ResultFactory $resultFactory
     * @param SubscriptionHelper $subscriptionHelper
     * @param StoreManager $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResultFactory $resultFactory,
        SubscriptionHelper $subscriptionHelper,
        StoreManager $storeManager,
        LoggerInterface $logger
    ) {
        $this->_resultRedirect = $resultFactory;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
    }

    /**
     * Redirect /customer/account to /account/settings
     *
     * @param AbstractAccount $subject
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function afterExecute(
        AbstractAccount $subject
    )
    {
        try {
            // if this store uses subscription then check for login before continuing
            if ( $this->_subscriptionHelper->isActive( $this->_storeManager->getStore()->getId() ) ) {

                try {
                    $resultRedirect = $this->_resultRedirect->create( ResultFactory::TYPE_REDIRECT );
                    $resultRedirect->setUrl( '/account/settings' );

                    return $resultRedirect;

                } catch ( \Exception $e ) {
                    $message = 'Failed to redirect user to account settings after login.';
                    $this->_logger->error( $message );
                    throw new LocalizedException( __($message) );
                }
            }
        } catch ( \Exception $e ) {
            $message = 'Failed to test subscription active state post login to redirect users to account settings.';
            $this->_logger->error( $message );
            throw new LocalizedException( __($message) );
        }
    }
}
