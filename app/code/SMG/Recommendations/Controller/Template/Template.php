<?php

namespace SMG\Recommendations\Controller\Template;

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

        try {
            $http = new Client();
            $results = $http->get(filter_var($this->_helper->getNewQuizApiPath(), FILTER_SANITIZE_URL));
            $json = $results->getBody()->getContents();

            $result = $this->_resultFactory->create(ResultFactory::TYPE_JSON);

            return $result->setJsonData($json);
        } catch(\Exception $e) {
            $result = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
            $json = array(
                'status_code'       => $e->getResponse()->getStatusCode(),
                'error_message'     => $e->getResponse()->getReasonPhrase()
            );
            return $result->setJsonData(json_encode($json));
        }
    }
}
