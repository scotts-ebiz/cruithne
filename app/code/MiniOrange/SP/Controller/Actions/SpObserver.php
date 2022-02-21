<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use MiniOrange\SP\Helper\Exception\SAMLResponseException;
use MiniOrange\SP\Helper\Exception\InvalidSignatureInResponseException;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Controller\Actions\ReadResponseAction;
use MiniOrange\SP\Helper\SPConstants;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use MiniOrange\SP\Helper\SPUtility;
use Psr\Log\LoggerInterface;

class SpObserver extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    private $requestParams = [
        'SAMLRequest',
        'SAMLResponse',
        'option'
    ];

    protected $messageManager;
    protected $logger;
    protected $readResponseAction;
    protected $spUtility;
    protected $adminLoginAction;
    protected $testAction;
    protected $currentControllerName;
    protected $currentActionName;
    protected $readLogoutRequestAction;
    protected $request;
    protected $resultFactory;
    protected $_pageFactory;
    protected $formkey;
    protected $acsUrl;

    public function __construct(
        ManagerInterface $messageManager,
        Context $context,
        LoggerInterface $logger,
        \MiniOrange\SP\Controller\Actions\ReadResponseAction $readResponseAction,
        SPUtility $spUtility,
        AdminLoginAction $adminLoginAction,
        Http $httpRequest,
        ReadLogoutRequestAction $readLogoutRequestAction,
        RequestInterface $request,
        ShowTestResultsAction $testAction,
        ResultFactory $resultFactory,
        PageFactory $pageFactory,
        FormKey $formkey
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->readResponseAction = $readResponseAction;
        $this->spUtility = $spUtility;
        $this->adminLoginAction = $adminLoginAction;
        $this->readLogoutRequestAction = $readLogoutRequestAction;
        $this->currentActionName = $httpRequest->getActionName();
        $this->request = $request;
        $this->testAction = $testAction;
        $this->resultFactory=$resultFactory;
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        // error_log("in spobserver construct".print_r($this->getRequest()->getParams(),TRUE));
        $this->formkey=$formkey;
        $this->getRequest()->setParam('form_key', $this->formkey->getFormKey());
        // error_log("in spobserver construct".$this->getRequest()->getParam('form_key'));
        // error_log("in spobserver construct".print_r($this->getRequest()->getParams(),TRUE));
    }
    
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {

        $keys=array_keys($this->request->getParams());
        $operation=array_intersect($keys, $this->requestParams);
        try {
            error_log("inside try");
            $params= $this->request->getParams();
            $postData= $this->request->getPost();
            $isTest=array_key_exists('RealayState', $params) && $params['RelayState']==SPConstants::TEST_RELAYSTATE;
            if (count($operation) > 0) {
                error_log("inside if");
                $result = $this->_route_data(array_values($operation)[0], $params, $postData);
                if (!$this->spUtility->isBlank($result)) {
                    // $observer->getControllerAction()->getResponse()->setRedirect($result);
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    $resultRedirect->setUrl($result);
                    error_log("before return");
                    return $resultRedirect;
                    
                }
            }

        } catch (\Exception $e) {
            if ($isTest) { // show a failed validation screen
                $this->testAction->setSamlException($e)->setHasExceptionOccurred(true)->execute();
            }
        }
//         $this->messageManager->addErrorMessage($e->getMessage());
//         $this->logger->debug($e->getMessage());
//        return $this->_pageFactory->create();
    }
    
    private function _route_data($op, $params, $postData)
    {
        switch ($op) {

            case $this->requestParams[0]:
                return $this->readLogoutRequestAction->setRequestParam($params)
                    ->setPostParam($postData)->execute();
            case $this->requestParams[1]:
                return $this->readResponseAction->setRequestParam($params)
                    ->setPostParam($postData)->execute();
            case $this->requestParams[2]:
                if ($params['option']==SPConstants::LOGIN_ADMIN_OPT) {
                    return $this->adminLoginAction->setRequestParam($params)->execute();
                }
        }
    }
}
