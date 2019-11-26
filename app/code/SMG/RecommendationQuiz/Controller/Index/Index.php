<?php

namespace SMG\RecommendationQuiz\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {

        // Check to make sure that the module is enabled at the store level
        if ( ! $recommendationHelper->isActive($storeManager->getStore()->getId())) {
            throw new \Magento\Framework\Exception\NotFoundException(__('File not Found'));
        }

        parent::__construct($context);

        $this->_resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();

        return $resultPage;
    }
}
