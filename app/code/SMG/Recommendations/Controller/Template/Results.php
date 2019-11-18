<?php

namespace SMG\Recommendations\Controller\Template;

use GuzzleHttp\Client;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use SMG\Recommendations\Helper\QuizHelper as RecommendationsQuizHelper;

class Results extends Action
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

        if( ! $this->_helper->getCompletedQuizApiPath() ) {
            return;
        }

        try {
            $http = new Client();
            $quiz_id = 'cdaf7de7-115c-41be-a7e4-3259d2f511f8';
            $results = $http->get(filter_var($this->_helper->getCompletedQuizApiPath() . '/' . $quiz_id, FILTER_SANITIZE_URL));
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
