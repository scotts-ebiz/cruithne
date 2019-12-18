<?php

namespace SMG\SubscriptionCheckout\Plugin\Controller\Cart;

use Magento\Framework\Exception\NotFoundException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;

/**
 * Class Index
 *
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
    public function __construct(LoggerInterface $logger,
        SubscriptionHelper $subscriptionHelper,
        StoreManagerInterface $storeManager)
    {
        $this->_logger = $logger;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * Check if this is a subscription otherwise continue
     *
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     * @return mixed
     */
    public function beforeExecute(\Magento\Checkout\Controller\Cart\Index $subject)
    {
        try
        {
            // if this is a subscription site we do not want them to go to the checkout cart page
            if ( $this->_subscriptionHelper->isActive($this->_storeManager->getStore()->getId()))
            {
                throw new NotFoundException(__('404'));
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }
    }
}
