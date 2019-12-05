<?php

namespace SMG\SubscriptionCheckout\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index extends \Magento\Checkout\Controller\Index\Index implements HttpGetActionInterface
{
    /**
     * @var \Magento\Checkout\Helper\Data 
     */
    protected $_checkoutHelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data 
     */
    protected $_urlHelper;

    /**
     * Index constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        array $data = []
    ) {
        $this->_checkoutHelper = $checkoutHelper;
        $this->_urlHelper = $urlHelper;
        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $coreRegistry,
            $translateInline,
            $formKeyValidator,
            $scopeConfig,
            $layoutFactory,
            $quoteRepository,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $resultJsonFactory,
            $data
        );
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $quote = $this->getOnepage()->getQuote();

        /**
         * If the customer is not logged in and guest checkout is not allowed,
         * redirect the customer to the login page. Set current URL (/checkout) as referer,
         * so the customer is redirected to checkout page on successful login.
         */
        if( ! $this->_customerSession->isLoggedIn() && ! $this->_checkoutHelper->isAllowedGuestCheckout( $quote ) ) {
            $resultRedirect = $this->resultRedirectFactory->create();

            $params = array(
                'quiz_id'   => 'cdaf7de7-115c-41be-a7e4-3259d2f511f8'
            );

            $customerLoginUrl = $this->_url->getUrl( 
                'customer/account/login',
                array(
                    'referer'   => $this->_urlHelper->getEncodedUrl( $this->_url->getCurrentUrl() ),
                    '_query'    => $params
                )
            );

            return $resultRedirect->setPath($customerLoginUrl);
        }

        if ( ! $this->isSecureRequest() ) {
            $this->_customerSession->regenerateId();
        }

        $this->_objectManager->get(\Magento\Checkout\Model\Session::class)->setCartWasUpdated(false);
        $this->getOnepage()->initCheckout();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Checkout'));
        return $resultPage;
    }

    /**
     * @return bool
     */
    private function isSecureRequest(): bool
    {
        $request = $this->getRequest();

        $referrer = $request->getHeader('referer');
        $secure = false;

        if ($referrer) {
            $scheme = parse_url($referrer, PHP_URL_SCHEME);
            $secure = $scheme === 'https';
        }

        return $secure && $request->isSecure();
    }
}
