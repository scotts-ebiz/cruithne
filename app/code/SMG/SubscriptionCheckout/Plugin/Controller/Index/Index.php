<?php

namespace SMG\SubscriptionCheckout\Plugin\Controller\Index;

use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
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
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * Index constructor.
     * @param LoggerInterface $logger
     * @param SubscriptionHelper $subscriptionHelper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param CheckoutHelper $checkoutHelper
     * @param RedirectFactory $resultRedirectFactory
     * @param CoreSession $coreSession
     * @param UrlInterface $url
     * @param UrlHelper $urlHelper
     */
    public function __construct(
        LoggerInterface $logger,
        SubscriptionHelper $subscriptionHelper,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        CheckoutHelper $checkoutHelper,
        RedirectFactory $resultRedirectFactory,
        CoreSession $coreSession,
        UrlInterface $url,
        UrlHelper $urlHelper
    ) {
        $this->_logger = $logger;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
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
     * @return Redirect|void
     */
    public function aroundExecute(
        \Magento\Checkout\Controller\Index\Index $subject,
        callable $proceed
    ) {
        try {
            // if this store uses subscription then check for login before continuing
            if ($this->_subscriptionHelper->isActive($this->_storeManager->getStore()->getId())) {

                // get the onepage quote to see if the user is a logged in user or a guest user
                $quote = $subject->getOnepage()->getQuote();

                /**
                 * If the customer is not logged in and guest checkout is not allowed,
                 * redirect the customer to the login page. Set current URL (/checkout) as referer,
                 * so the customer is redirected to checkout page on successful login.
                 */
                if (!$this->_customerSession->isLoggedIn() && !$this->_checkoutHelper->isAllowedGuestCheckout($quote)) {
                    $resultRedirect = $this->_resultRedirectFactory->create();

                    $params = [
                        'quiz_id' => $this->_coreSession->getQuizId()
                    ];

                    $customerLoginUrl = $this->_url->getUrl(
                        'customer/account/create',
                        [
                            'referer' => $this->_urlHelper->getEncodedUrl($this->_url->getCurrentUrl()),
                            '_query' => $params
                        ]
                    );

                    // return the login page
                    return $resultRedirect->setPath($customerLoginUrl);
                } else {
                    // This hopefully helps prevent some of the issues where the
                    // continue button on the shipping page does not appear.
                    $this->_customerSession->getCustomer()->cleanAllAddresses();

                    // The customer is logged in, so check if they have any
                    // subscription details in the session.
                    if ($this->_coreSession->getData('subscription_details')) {
                        // We are adding the subscription to the cart so clear
                        // out the onepage quote.
                        $addresses = $quote->getAllAddresses();
                        foreach ($addresses as $address) {
                            $address->delete();
                        }

                        $quote->removeAllItems();
                        $quote->removeAllAddresses();
                        $quote->save();
                        $quote->collectTotals()->save();
                        $this->_subscriptionHelper->addSessionSubscriptionToCart();

                        $details = $this->_coreSession->getData('subscription_details');

                        // Set coupon code if annual subscription.
                        if (isset($details['subscription_plan']) && $details['subscription_plan'] == 'annual') {
                            $this->_checkoutSession->getQuote()->setCouponCode('annual_discount')->save();
                        }
                    }

                    return $proceed();
                }
            } else {
                return $proceed();
            }
        } catch (\Exception $e) {
            $this->_logger->error($e);
            return $proceed();
        }
    }
}
