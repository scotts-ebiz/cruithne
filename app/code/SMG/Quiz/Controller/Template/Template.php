<?php

namespace SMG\Quiz\Controller\Template;

use GuzzleHttp\Client;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Template extends Action
{
    private $_resultFactory;

    public function __construct(Context $context, ResultFactory $resultFactory)
    {
        parent::__construct($context);
        $this->_resultFactory = $resultFactory;
    }

    public function execute()
    {
        $http = new Client();
        $results = $http->get('https://lspaasdraft.azurewebsites.net/api/quizzes/template');
        $json = $results->getBody()->getContents();

        $result = $this->_resultFactory->create(ResultFactory::TYPE_JSON);

        return $result->setJsonData($json);
    }
}
