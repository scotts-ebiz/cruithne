<?php

namespace SMG\SubscriptionCheckout\Controller\Cart;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Exception\NotFoundException;

/**
 * Class Index
 * @package SMG\SubscriptionCheckout\Controller\Cart
 * @todo Wes this needs jailed
 */
class Index extends \Magento\Checkout\Controller\Cart implements HttpGetActionInterface
{

    /**
     * @var \SMG\SubscriptionApi\Helper\SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
    )
    {
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;

        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * Prevent direct access to the cart
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if ( $this->_subscriptionHelper->isActive( $this->_storeManager->getStore()->getId() ) ) {
            throw new NotFoundException(__('404'));
        }
    }
}
