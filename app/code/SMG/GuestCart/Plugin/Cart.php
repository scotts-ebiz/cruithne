<?php

namespace SMG\GuestCart\Plugin;

use \Magento\Quote\Model\QuoteFactory;
use \Magento\Checkout\Model\Session;
use \Magento\Quote\Model\QuoteIdMaskFactory;
use \Magento\Framework\App\Request\Http;
use \Magento\Framework\App\ResponseFactory;
use \Magento\Framework\UrlInterface;
use \Magento\Framework\App\Response\RedirectInterface;
use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\App\RequestInterface;

class Cart
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        QuoteFactory $quoteFactory,
        Session $checkoutSession,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Http $request,
        ResponseFactory $responseFactory,
        UrlInterface $url,
        RedirectInterface $redirect
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
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
            $this->quoteFactory->create()->load($_quoteId);
            $this->checkoutSession->setQuoteId($_quoteId);
            $redirectionUrl = $this->url->getUrl('checkout/cart/index');
            $redirectionUrl = $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
            return $proceed($redirectionUrl);
        }
        return $proceed($request);
    }
}