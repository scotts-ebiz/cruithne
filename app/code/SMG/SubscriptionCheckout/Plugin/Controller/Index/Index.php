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
use Magento\Framework\Controller\ResultFactory;
use SMG\RecommendationApi\Helper\RecommendationHelper;

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
    protected $_messageManager;
    protected $_recommendationHelper;

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
        UrlHelper $urlHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        RecommendationHelper $recommendationHelper
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
        $this->_messageManager = $messageManager;
        $this->_recommendationHelper = $recommendationHelper;
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
            $this->_coreSession->setOrderProcessing(0);

            // if this store uses subscription then check for login before continuing
            if ($this->_subscriptionHelper->isActive($this->_storeManager->getStore()->getId())) {
                /**
                 * If the customer is not logged in and guest checkout is not allowed,
                 * redirect the customer to the login page. Set current URL (/checkout) as referer,
                 * so the customer is redirected to checkout page on successful login.
                 */
                if (! $this->_customerSession->isLoggedIn() && !$this->_checkoutHelper->isAllowedGuestCheckout($subject->getOnepage()->getQuote())) {
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
                        
                        /*check start quiz time is not exceed from 2 week*/
                        $startQuiz = $this->_coreSession->getTimeStamp();
                        if(!empty($startQuiz))
                        {   
                            $convertedDate = date('Y-m-d',$startQuiz);
                            $startYear = date('Y',$startQuiz);
                            $todayyear = date('Y');
                            $startDate = new \DateTime($convertedDate);
                            $todayDate = new \DateTime();
                            $days  = $todayDate->diff($startDate)->format('%a');
                            $quiz_id = $this->_coreSession->getData('quiz_id');
                            if($days >= $this->_recommendationHelper->getExpiredDays($this->_storeManager->getStore()->getId()) && $startYear == $todayyear)
                            {
                                 $message = "Quiz Id ".$quiz_id." Expired";
                                 $this->_messageManager->addError(__('Looks like your quiz results are out of date.
                                     To make sure you receive the most accurate recommendation,  
                                     please retake the Quiz.<a href="/quiz" >Take the quiz</a>.'));
                                 $this->logger->error(print_r($message,true));
                                 $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                                 $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                                 return $resultRedirect;
                                 exit;
                            }               
                        }
                        
                        $this->_subscriptionHelper->addSessionSubscriptionToCart();

                        // Add the checkout session quote to the checkout page.
                        $subject->getOnepage()->setQuote($this->_checkoutSession->getQuote());

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
