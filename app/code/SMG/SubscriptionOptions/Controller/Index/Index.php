<?php

namespace SMG\SubscriptionOptions\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use SMG\RecommendationApi\Helper\RecommendationHelper;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;

/**
 * Class Index
 * @package SMG\SubscriptionOptions\Controller\Index
 * @todo Wes this needs jailed
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var RecommendationHelper
     */
    protected $_helper;
    
    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;
    protected $_messageManager;
    protected $logger;
    protected $_storeManager;
    
    /**
     * @var SubscriptionCollectionFactory
    */
    protected $_subscriptionCollectionFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param RecommendationHelper $helper
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        RecommendationHelper $helper,
        SessionManagerInterface $coreSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SubscriptionCollectionFactory $subscriptionCollectionFactory
    ) {
        $this->_helper = $helper;
        parent::__construct($context);

        $this->_pageFactory = $pageFactory;
        $this->_coreSession = $coreSession;
        $this->_messageManager = $messageManager;
        $this->logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
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
        
        if(!empty($quizid) && !empty($zip))
        {
            $subscriptions = $this->_subscriptionCollectionFactory->create();
            $subscription = $subscriptions
                ->addFieldToFilter('quiz_id', $quizid)
                ->getFirstItem();
                
            if($subscription && $subscription->getId()){
                
                $timestamp = strtotime($subscription->getData('created_at'));
                $this->_coreSession->setTimeStamp($timestamp);
            }
            else
            {
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
            if($days >= $this->_helper->getExpiredDays($this->_storeManager->getStore()->getId()) || $startYear != $todayyear)
            {
                 $message = "Quiz Id ".$quiz_id." Expired";
                 $this->_messageManager->addError(__('Looks like your quiz results are out of date.
                 To make sure you receive the most accurate recommendation,  
                 please retake the Quiz.<a href="/quiz" >Take the quiz</a>.'));
                 $this->logger->error(print_r($message,true));
                 if(empty($quizid) && empty($zip))
                {
                     $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                     $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                     return $resultRedirect;
                     exit;
                }
            }               
        }
        
        $page = $this->_pageFactory->create();

        return $page;
    }
}