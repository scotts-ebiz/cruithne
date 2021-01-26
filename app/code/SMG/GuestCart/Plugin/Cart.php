<?php

namespace SMG\GuestCart\Plugin;

use \Magento\Quote\Model\QuoteFactory;
use \Magento\Checkout\Model\Session;
use \Magento\Quote\Model\QuoteIdMaskFactory;
use \Magento\Framework\App\Request\Http;
use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\App\RequestInterface;

class Cart
{
    public function __construct(
        QuoteFactory $quoteFactory,
        Session $checkoutSession,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Http $request
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->request = $request;
    }

    public function aroundDispatch(
        ActionInterface $subject,
        \Closure $proceed,
        RequestInterface $request
    ) {
        $quoteId = $this->request->getParam('cart');
        if ($quoteId) {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
            $_quoteId = $quoteIdMask->getQuoteId();
            if(!empty($_quoteId)){
                $this->quoteFactory->create()->load($_quoteId);
                $this->checkoutSession->setQuoteId($_quoteId);
            }
        }
        return $proceed($request);
    }
}