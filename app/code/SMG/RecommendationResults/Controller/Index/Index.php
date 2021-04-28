<?php

namespace SMG\RecommendationResults\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use SMG\RecommendationApi\Helper\RecommendationHelper;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;
    
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;
    protected $_messageManager;
    protected $resultFactory;
    protected $logger;
    protected $_recommendationHelper;
    protected $_storeManager;
    
    /**
     * @var SubscriptionCollectionFactory
    */
    protected $_subscriptionCollectionFactory;
    
    /**
     * @var CookieManagerInterface
    */
    private $_cookieManager;
    
    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param RecommendationHelper $recommendationHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\NotFoundException
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SessionManagerInterface $coreSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ResultFactory $resultFactory,
        \Psr\Log\LoggerInterface $logger,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        CookieManagerInterface $cookieManager
    ) {

        // Check to make sure that the module is enabled at the store level
        if ( ! $recommendationHelper->isActive($storeManager->getStore()->getId())) {
            throw new \Magento\Framework\Exception\NotFoundException(__('File not Found'));
        }
        parent::__construct($context);
        $this->_pageFactory = $pageFactory;
        $this->_coreSession = $coreSession;
        $this->_messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
        $this->logger = $logger;  
        $this->_recommendationHelper = $recommendationHelper;  
        $this->_storeManager = $storeManager;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_cookieManager = $cookieManager;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /*check start quiz time is not exceed from 2 week*/
        $this->_coreSession->start();
        $quizid = $this->getRequest()->getParam('id');
        $zip = $this->getRequest()->getParam('zip');
        $this->_cookieManager->deleteCookie('mage-messages');
        
        if(!empty($quizid) && !empty($zip))
        {
            $subscriptions = $this->_subscriptionCollectionFactory->create();
            $subscription = $subscriptions
                ->addFieldToFilter('quiz_id', $quizid)
                ->getFirstItem();
                
            if($subscription && $subscription->getId()){
                
                $timestamp = strtotime($subscription->getData('created_at'));
                $this->_coreSession->setTimeStamp($timestamp);
                
            }else{
                
                 $this->_coreSession->unsTimeStamp();
                 $this->_messageManager->addError(__('Looks like your quiz results were not found.
                 To make sure you receive the most accurate recommendation,  
                 please retake the Quiz.<a href="/quiz" >Take the quiz</a>.'));
            }
        }
        
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

            if($days >= $this->_recommendationHelper->getExpiredDays($this->_storeManager->getStore()->getId()) || $startYear != $todayyear)
            {
                $message = "Quiz Id ".$quiz_id." Expired";
                $this->_messageManager->addError(__('Looks like your quiz results are out of date.
                 To make sure you receive the most accurate recommendation,  
                 please retake the Quiz.<a href="/quiz" >Take the quiz</a>.'));
                $this->logger->error(print_r($message,true));
            }               
        }
        
        return $this->_pageFactory->create();
    }
}