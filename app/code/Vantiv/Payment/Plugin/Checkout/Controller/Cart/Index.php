<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Plugin\Checkout\Controller\Cart;

class Index
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Remove gift card input if cart contains subscription items
     *
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     * @param \Magento\Framework\View\Result\Page $result
     * @return \Magento\Framework\View\Result\Page
     */
    public function afterExecute(\Magento\Checkout\Controller\Cart\Index $subject, $result)
    {
        if (!($result instanceof \Magento\Framework\View\Result\Page
            && $result->getLayout()
            && ($giftCardBlock = $result->getLayout()->getBlock('checkout.cart.giftcardaccount')))
        ) {
            return $result;
        }

        $quote = $this->checkoutSession->getQuote();
        if ($quote && $this->recurringHelper->quoteContainsSubscription($quote)) {
            $giftCardBlock->setTemplate('');
        }

        return $result;
    }
}
