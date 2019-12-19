<?php

namespace SMG\SubscriptionCheckout\Plugin\Controller\Account;

/**
 * Class LoginPost
 * @package SMG\SubscriptionCheckout\Controller\Account
 * @todo Wes this needs jailed
 */
class LoginPost
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \SMG\SubscriptionApi\Helper\SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * LoginPost constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_request = $request;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(\Magento\Customer\Controller\Account\LoginPost $subject, $result)
    {
        // if this is a subscription site we do not want them to go to the checkout cart page
        if ($this->_subscriptionHelper->isActive( $this->_storeManager->getStore()->getId()))
        {
            return $result;
        }
    }
}
