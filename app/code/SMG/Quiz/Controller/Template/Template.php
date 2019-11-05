<?php

namespace SMG\Quiz\Controller\Template;

use GuzzleHttp\Client;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use SMG\Recommendations\Helper\QuizHelper as RecommendationsQuizHelper;

class Template extends Action
{
    private $_resultFactory;

    protected $_helper;

    public function __construct(Context $context, ResultFactory $resultFactory, RecommendationsQuizHelper $helper)
    {
        parent::__construct($context);
        $this->_resultFactory = $resultFactory;
        $this->_helper = $helper;
    }

    public function execute()
    {
        if( ! $this->_helper->getNewQuizApiPath() ) {
            return;
        }

        $http = new Client();
        $results = $http->get(filter_var($this->_helper->getNewQuizApiPath(), FILTER_SANITIZE_URL));
        $json = $results->getBody()->getContents();

        $result = $this->_resultFactory->create(ResultFactory::TYPE_JSON);

        return $result->setJsonData($json);
    }
}
