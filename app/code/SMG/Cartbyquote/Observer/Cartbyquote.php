<?php

namespace SMG\Cartbyquote\Observer;

use \Magento\Quote\Model\QuoteFactory;
use \Magento\Checkout\Model\Session;
use \Magento\Quote\Model\QuoteIdMaskFactory;
use \Magento\Framework\App\Request\Http;
use \Magento\Framework\App\ActionFlag;
use \Magento\Framework\UrlInterface;

class Cartbyquote implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        QuoteFactory $quoteFactory,
        Session $checkoutSession,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Http $request,
        ActionFlag $actionFlag,
        UrlInterface $url
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->request = $request;
        $this->actionFlag = $actionFlag;
        $this->url = $url;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteId = $this->request->getParam('cart');
        if ($quoteId) {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
            $this->quoteFactory->create()->load($quoteIdMask->getQuoteId());
            $this->checkoutSession->setQuoteId($quoteIdMask->getQuoteId());
            $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
            $observer->getControllerAction()->getResponse()->setRedirect(
                $this->url->getUrl("checkout/cart/index")
            );
        }
        return $this;
    }
}
