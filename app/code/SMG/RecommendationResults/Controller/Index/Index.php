<?php

namespace SMG\RecommendationResults\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use SMG\Recommendations\Helper\QuizHelper;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var QuizHelper
     */
    protected $_helper;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param QuizHelper $helper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        QuizHelper $helper
    ) {
        $this->_helper = $helper;
        parent::__construct($context);

        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();

        return $resultPage;
    }
}
