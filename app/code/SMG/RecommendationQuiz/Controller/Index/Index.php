<?php

namespace SMG\RecommendationQuiz\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Session\SessionManagerInterface as Session;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
    /**
     * @var Session
     */
    protected $_session;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Session $session
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Session $session
    ) {

        // Check to make sure that the module is enabled at the store level
        if (! $recommendationHelper->isActive($storeManager->getStore()->getId())) {
            throw new \Magento\Framework\Exception\NotFoundException(__('File not Found'));
        }

        parent::__construct($context);

        $this->_resultPageFactory = $resultPageFactory;
        $this->_session = $session;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // Starting a new quiz, so clear out any existing information.
        $this->_session->start();
        $this->_session->unsetData('quiz_id');
        $this->_session->unsetData('subscription_details');
        $timestamp = strtotime(date("Y-m-d H:i:s"));
        $this->_session->setTimeStamp($timestamp);
        $resultPage = $this->_resultPageFactory->create();

        return $resultPage;
    }
}
