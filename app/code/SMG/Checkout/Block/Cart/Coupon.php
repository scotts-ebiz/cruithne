<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\Checkout\Block\Cart;

use Psr\Log\LoggerInterface;

/**
 * @api
 * @since 100.0.2
 */
class Coupon extends \Magento\Checkout\Block\Cart\Coupon
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param LoggerInterface $logger
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = [])
    {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->_isScopePrivate = true;
    }

    public function isCouponValid()
    {
        // set default return value
        $retval = true;

        $isCouponValid = $this->_checkoutSession->getCouponValid();
        if (isset($isCouponValid))
        {
            $retval = $isCouponValid;

            // reset the value to true in case the error is given and then proceed button
            // is clicked and then the user returns to the cart
            $this->_checkoutSession->setCouponValid(true);
        }

        // return
        return $retval;
    }
}
