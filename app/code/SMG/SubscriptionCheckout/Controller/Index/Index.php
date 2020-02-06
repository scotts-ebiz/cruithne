<?php

namespace SMG\SubscriptionCheckout\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\SessionManagerInterface as Session;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var RedirectFactory
     */
    protected $_redirectFactory;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param RedirectFactory $redirectFactory
     * @param CustomerSession $customerSession
     * @param Session $session
     * @param UrlInterface $url
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        RedirectFactory $redirectFactory,
        CustomerSession $customerSession,
        Session $session,
        UrlInterface $url
    ) {
        parent::__construct($context);

        $this->_pageFactory = $pageFactory;
        $this->_redirectFactory = $redirectFactory;
        $this->_customerSession = $customerSession;
        $this->_session = $session;
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

        // We have reached the success page, so clear out any quiz data.
        $this->_session->unsetData('quiz_id');
        $this->_session->unsetData('subscription_details');

        return $this->_pageFactory->create();
    }
}
