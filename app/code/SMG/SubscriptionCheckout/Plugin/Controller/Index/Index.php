<?php

namespace SMG\SubscriptionCheckout\Plugin\Controller\Index;

use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Session\SessionManagerInterface as CoreSession;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;

class Index
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var CheckoutHelper
     */
    protected $_checkoutHelper;

    /**
     * @var RedirectFactory
     */
    protected $_resultRedirectFactory;

    /**
     * @var CoreSession
     */
    protected $_coreSession;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /**
     * @var UrlHelper
     */
    protected $_urlHelper;


    /**
     * Index constructor.
     *
     */
    public function __construct(LoggerInterface $logger,
        SubscriptionHelper $subscriptionHelper,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        CheckoutHelper $checkoutHelper,
        RedirectFactory $resultRedirectFactory,
        CoreSession $coreSession,
        UrlInterface $url,
        UrlHelper $urlHelper)
    {
        $this->_logger = $logger;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_resultRedirectFactory = $resultRedirectFactory;
        $this->_coreSession = $coreSession;
        $this->_url = $url;
        $this->_urlHelper = $urlHelper;
    }

    /**
     * Check to see if it is a subscription and if the user is logged in before continuing
     *
     * @param \Magento\Checkout\Controller\Index\Index $subject
     * @param callable $proceed
     * @return Redirect
     */
    public function beforeExecute(\Magento\Checkout\Controller\Index\Index $subject)
    {
        try
        {
            // if this store uses subscription then check for login before continuing
            if ($this->_subscriptionHelper->isActive($this->_storeManager->getStore()->getId()))
            {
                // get the onepage quote to see if the user is a logged in user or a guest user
                $quote = $subject->getOnepage()->getQuote();

                /**
                 * If the customer is not logged in and guest checkout is not allowed,
                 * redirect the customer to the login page. Set current URL (/checkout) as referer,
                 * so the customer is redirected to checkout page on successful login.
                 */
                if (!$this->_customerSession->isLoggedIn() && !$this->_checkoutHelper->isAllowedGuestCheckout($quote))
                {
                    $resultRedirect = $this->_resultRedirectFactory->create();

                    $params = array(
                        'quiz_id' => $this->_coreSession->getQuizId()
                    );

                    $customerLoginUrl = $this->_url->getUrl(
                        'customer/account/login',
                        array(
                            'referer' => $this->_urlHelper->getEncodedUrl($this->_url->getCurrentUrl()),
                            '_query' => $params
                        )
                    );

                    // return the login page
                    return $resultRedirect->setPath($customerLoginUrl);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }
    }
}
