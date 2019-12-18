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
 * @todo Wes this needs jailed
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
     * @param $result
     * @return mixed
     */
    public function afterExecute(\Magento\Checkout\Controller\Cart\Index $subject, $result)
    {
        $this->_logger->debug("******************************");
        $this->_logger->debug("I am in Cart/Index afterExecute");
        $this->_logger->debug("*****************************");

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

        // return
        return $result;
    }
}
