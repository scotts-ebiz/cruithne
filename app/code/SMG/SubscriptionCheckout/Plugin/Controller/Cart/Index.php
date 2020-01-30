<?php

namespace SMG\SubscriptionCheckout\Plugin\Controller\Cart;

use Magento\Framework\Exception\NotFoundException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;

/**
 * Class Index
 * @package SMG\SubscriptionCheckout\Plugin\Controller\Cart
 */
class Index
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Index constructor.
     *
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param SubscriptionHelper $subscriptionHelper
     */
    public function __construct(
        LoggerInterface $logger,
        SubscriptionHelper $subscriptionHelper,
        StoreManagerInterface $storeManager
    )
    {
        $this->_logger = $logger;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * Check if the store is active subscription store, if a subscription then return 404
     * as we do not want customers coming here.  If the store is not an active subscription
     * store, then continue on as normal.
     */
    public function beforeExecute()
    {
        // if this is a subscription site we do not want them to go to the checkout cart page
        if ( $this->_subscriptionHelper->isActive($this->_storeManager->getStore()->getId()))
        {
            $this->_logger->error('Attempting to hit cart page in subscription flow.: 404');
            throw new NotFoundException(__('404'));
        }
    }
}
