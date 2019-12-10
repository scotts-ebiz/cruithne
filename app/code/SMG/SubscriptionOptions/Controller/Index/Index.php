<?php

namespace SMG\SubscriptionOptions\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use SMG\RecommendationApi\Helper\RecommendationHelper;

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
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param RecommendationHelper $helper
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        RecommendationHelper $helper
    ) {
        $this->_helper = $helper;
        parent::__construct($context);

        $this->_pageFactory = $pageFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $page = $this->_pageFactory->create();

        return $page;
    }
}
