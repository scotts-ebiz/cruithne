<?php

namespace SMG\SubscriptionCheckout\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var RedirectFactory
     */
    protected $_redirectFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param RedirectFactory $redirectFactory
     * @param Session $customerSession
     * @param UrlInterface $url
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        RedirectFactory $redirectFactory,
        Session $customerSession,
        UrlInterface $url
    ) {
        parent::__construct($context);

        $this->_pageFactory = $pageFactory;
        $this->_redirectFactory = $redirectFactory;
        $this->_customerSession = $customerSession;
        $this->_url = $url;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // Customer is not logged in so redirect to the home page.
        if (! $this->_customerSession->isLoggedIn()) {
            $redirect = $this->_redirectFactory->create();
            $url = $this->_url->getUrl('/');

            return $redirect->setPath($url);
        }

        return $this->_pageFactory->create();
    }
}
