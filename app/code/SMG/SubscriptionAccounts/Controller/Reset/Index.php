<?php

namespace SMG\SubscriptionAccounts\Controller\Reset;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Url\Helper\Data as UrlHelper;

/**
 * Class Index
 * @package SMG\SubscriptionAccounts\Controller\Reset
 */
class Index extends Action
{
    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var RedirectFactory
     */
    protected $_redirectFactory;

    /**
     * @var UrlHelper
     */
    protected $_urlHelper;

    /**
     * @param CustomerSession $customerSession
     * @param RedirectFactory $redirectFactory
     * @param UrlHelper $urlHelper
     * @param Context $context
     */
    public function __construct(
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        UrlHelper $urlHelper,
        Context $context
    ) {
        $this->_customerSession = $customerSession;
        $this->_redirectFactory = $redirectFactory;
        $this->_urlHelper = $urlHelper;
        parent::__construct($context);
    }

    /**
     * Check that the user should have access
     *
     * @param RequestInterface $request
     * @return Redirect|ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        // Check if the user is logged in and redirect to settings
        if ($this->_customerSession->isLoggedIn()) {
            $resultRedirect = $this->_redirectFactory->create();
            $redirect = $this->_url->getUrl(
                'account/settings',
                [
                    'referer' => $this->_urlHelper->getEncodedUrl($this->_url->getCurrentUrl())
                ]
            );
            return $resultRedirect->setPath($redirect);
        }

        // Check that the proper get parameters exist or redirect to login
        if (
            ! array_key_exists('apiKey', $this->getRequest()->getParams())
            ||
            ! array_key_exists('pwrt', $this->getRequest()->getParams())
        ) {
            $resultRedirect = $this->_redirectFactory->create();
            $redirect = $this->_url->getUrl(
                'customer/account/login',
                [
                    'referer' => $this->_urlHelper->getEncodedUrl($this->_url->getCurrentUrl())
                ]
            );
            return $resultRedirect->setPath($redirect);
        }

        return parent::dispatch($request);
    }

    /**
     * Display reset password page
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Reset Password'));
        $this->_view->renderLayout();
    }
}
