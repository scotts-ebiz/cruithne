<?php

namespace SMG\RecommendationResults\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use SMG\RecommendationApi\Helper\RecommendationHelper;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param RecommendationHelper $recommendationHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {

        // Check to make sure that the module is enabled at the store level
        if ( ! $recommendationHelper->isActive($storeManager->getStore()->getId())) {
            throw new \Magento\Framework\Exception\NotFoundException(__('File not Found'));
        }
        parent::__construct($context);
        $this->_pageFactory = $pageFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
