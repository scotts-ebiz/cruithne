<?php

namespace SMG\Recommendations\Controller\Results;

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

    protected $_helper;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        QuizHelper $helper
    ) {
        $this->_helper = $helper;
        parent::__construct($context);

        $this->_resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();

        return $resultPage;
    }
}
