<?php

namespace SMG\SubscriptionCheckout\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use Magento\Store\Model\StoreManagerInterface;
use SMG\RecommendationApi\Helper\RecommendationHelper;
use Magento\Framework\Controller\ResultFactory;
/**
 * Class OrderManagement
 */
class OrderManagement
{
	
	
	/**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;
	
	/**
     * @var SubscriptionHelper
     */
    protected $_subscriptionHelper;
	
	/**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
	protected $_recommendationHelper;
	protected $_messageManager;

    /**
     * OrderInterface constructor.
     * @param LoggerInterface $logger
	 * @param SessionManagerInterface $coreSession
	 * @param SubscriptionHelper $subscriptionHelper
	 * @param StoreManagerInterface $storeManager
	 * @param RecommendationHelper $recommendationHelper
     */
    public function __construct(LoggerInterface $logger,
		SessionManagerInterface $coreSession,
		SubscriptionHelper $subscriptionHelper,
		StoreManagerInterface $storeManager,
		RecommendationHelper $recommendationHelper,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		ResultFactory $resultFactory) {
			
        $this->_logger = $logger;
		$this->_coreSession = $coreSession;
		$this->_subscriptionHelper = $subscriptionHelper;
		$this->_storeManager = $storeManager;
		$this->_recommendationHelper = $recommendationHelper;
		$this->_messageManager = $messageManager;
    }
	
    /**
     * @param OrderManagementInterface $subject
     * @param OrderInterface           $order
     *
     * @return OrderInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePlace(
        OrderManagementInterface $subject,
        OrderInterface $order
    ): array {
        $quoteId = $order->getQuoteId();
        if ($quoteId) {
        	if ($this->_subscriptionHelper->isActive($this->_storeManager->getStore()->getId())) {
				$this->_coreSession->start();
					
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
					if($days >= $this->_recommendationHelper->getExpiredDays($this->_storeManager->getStore()->getId()) || $startYear != $todayyear)
					{
						$message = "Quiz Id ".$quiz_id." Expired";
						$this->_logger->error(print_r($message,true));
						$message='Looks like your quiz results are out of date. To make sure you receive the most accurate recommendation,  please retake the Quiz.<a href="/quiz">Take the quiz</a>.';
						throw new InputException(__($message));
						exit;
					}				
				}
			}
        }
        return [$order];
    }
}