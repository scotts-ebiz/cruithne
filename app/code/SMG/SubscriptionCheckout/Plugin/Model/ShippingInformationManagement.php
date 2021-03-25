<?php

namespace SMG\SubscriptionCheckout\Plugin\Model;

use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use Magento\Store\Model\StoreManagerInterface;
use SMG\RecommendationApi\Helper\RecommendationHelper;

class ShippingInformationManagement
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;
    protected $_messageManager;

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

    /**
     * ShippingInformationManagement constructor.
     *
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(\Magento\Framework\Message\ManagerInterface $messageManager,
        LoggerInterface $logger,
		SessionManagerInterface $coreSession,
		SubscriptionHelper $subscriptionHelper,
		StoreManagerInterface $storeManager,
		RecommendationHelper $recommendationHelper)
    {
        $this->_messageManager = $messageManager;
        $this->_logger = $logger;
		$this->_coreSession = $coreSession;
		$this->_subscriptionHelper = $subscriptionHelper;
		$this->_storeManager = $storeManager;
		$this->_recommendationHelper = $recommendationHelper;
    }

    public function beforeSaveAddressInformation(\Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation)
    {
        try
        {
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
					}
				}
			}

        }
        catch (\Exception $e)
        {
            $this->_logger->error($e);
        }

        return [$cartId, $addressInformation];
    }

    public function afterSaveAddressInformation(\Magento\Checkout\Model\ShippingInformationManagement $shipping, $result)
    {
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
					}
				}
		}

        return  $result;
    }
}
