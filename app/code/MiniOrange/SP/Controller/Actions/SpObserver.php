<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\App\Action\Action;

use MiniOrange\SP\Helper\Exception\SAMLResponseException;
use MiniOrange\SP\Helper\Exception\InvalidSignatureInResponseException;
use MiniOrange\SP\Helper\SPMessages;
use Magento\Framework\Event\Observer;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Controller\Actions\ReadResponseAction;
use MiniOrange\SP\Helper\SPConstants;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;


/**
 * This is our main Observer class. Observer class are used as a callback
 * function for all of our events and hooks. This particular observer
 * class is being used to check if a SAML request or response was made
 * to the website. If so then read and process it. Every Observer class
 * needs to implement ObserverInterface.
 */
class SpObserver extends Action implements CsrfAwareActionInterface
{
    private $requestParams = array (
        'SAMLRequest',
        'SAMLResponse',
        'option'
    );

    private $controllerActionPair = array (
        'account' => array('login','create'),
        'auth' => array('login'),
    );

    protected $messageManager;
    protected $logger;
    protected $readResponseAction;
    protected $spUtility;
    protected $adminLoginAction;
    protected $testAction;
	protected $storeManager;
    protected $currentControllerName;
    protected $currentActionName;
    protected $readLogoutRequestAction;
    protected $requestInterface;
    protected $request;
    protected $formkey;
    protected $_pageFactory;
    protected $acsUrl;
    protected $repostSAMLResponseRequest;
    protected $repostSAMLResponsePostData;

    public function __construct(\Magento\Framework\Message\ManagerInterface $messageManager,
                                \Psr\Log\LoggerInterface $logger,
                                \Magento\Backend\App\Action\Context $context,
                                \MiniOrange\SP\Controller\Actions\ReadResponseAction $readResponseAction,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \MiniOrange\SP\Controller\Actions\AdminLoginAction $adminLoginAction,
                                \Magento\Framework\App\Request\Http $httpRequest,
                                \MiniOrange\SP\Controller\Actions\ReadLogoutRequestAction $readLogoutRequestAction,
                                \Magento\Framework\App\RequestInterface $request,
								\Magento\Store\Model\StoreManagerInterface $storeManager,
                                \MiniOrange\SP\Controller\Actions\ShowTestResultsAction $testAction,
                                ResultFactory $resultFactory,
                                \Magento\Framework\View\Result\PageFactory $pageFactory,
                                \Magento\Framework\Data\Form\FormKey $formkey)
    {
        //You can use dependency injection to get any class this observer may need.
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->readResponseAction = $readResponseAction;
        $this->spUtility = $spUtility;
        $this->adminLoginAction = $adminLoginAction;
        $this->readLogoutRequestAction = $readLogoutRequestAction;
        $this->currentControllerName = $httpRequest->getControllerName();
        $this->currentActionName = $httpRequest->getActionName();
        $this->request = $request;
        $this->testAction = $testAction;
        $this->storeManager = $storeManager;
        $this->resultFactory=$resultFactory;
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->formkey=$formkey;
        $this->getRequest()->setParam('form_key', $this->formkey->getFormKey());
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    /**
     * This function is called as soon as the observer class is initialized.
     * Checks if the request parameter has any of the configured request
     * parameters and handles any exception that the system might throw.
     *
     * @param $observer
     */
    public function execute()
    {
        $keys 			= array_keys($this->request->getParams());
        $operation 		= array_intersect($keys,$this->requestParams);
        try{
            $params = $this->request->getParams(); // get params
            $postData = $this->request->getPost(); // get only post params
            $isTest = isset($params['RelayState']) && $params['RelayState']==SPConstants::TEST_RELAYSTATE;
            // request has values then it takes priority over others 
            if(count($operation) > 0) {
                $this->_route_data(array_values($operation)[0], $params, $postData);
               if (!$this->spUtility->isBlank($result)) {
                   // $observer->getControllerAction()->getResponse()->setRedirect($result);
                   $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                   $resultRedirect->setUrl($result);
                   return $resultRedirect;
          }
            }
            
        }catch (\Exception $e){
            if($isTest) { // show a failed validation screen
                $this->testAction->setSamlException($e)->setHasExceptionOccurred(TRUE)->execute();
            }
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->debug($e->getMessage());
			echo ' <div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong>We could not sign you in. Please contact your Administrator.</p></div>';
            exit;
        }
    }


    /**
     * This function checks if user needs to be redirected to the
     * registered IDP with AUthnRequest. First check if admin has
     * enabled autoRedirect. Then check if user is landing on one of the
     * admin or customer login pages. If both of those are true
     * then return TRUE other return FALSE.
     */
    private function checkIfUserShouldBeRedirected()
    {
        // return false if auto redirect is not enabled
        if($this->spUtility->getStoreConfig(SPConstants::AUTO_REDIRECT)!="1"
            || $this->spUtility->isUserLoggedIn()) return FALSE;
        // check if backdoor is enabled and samlsso=false
        if($this->spUtility->getStoreConfig(SPConstants::BACKDOOR)=="1"
            && isset($this->request->getParams()[SPConstants::SAML_SSO_FALSE])) return FALSE;
        // now check if user is landing on one of the login pages
        $action = isset($this->controllerActionPair[$this->currentControllerName])
            ? $this->controllerActionPair[$this->currentControllerName] : NULL;
        return !is_null($action) && is_array($action) ? in_array($this->currentActionName,$action) : FALSE;
    }


    /**
     * Route the request data to appropriate functions for processing.
     * Check for any kind of Exception that may occur during processing
     * of form post data. Call the appropriate action.
     *
     * @param $op refers to operation to perform
     * @param $params
     * @param $postData
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    private function _route_data($op,$params,$postData)
    {
        switch ($op)
        {
            case $this->requestParams[0]:{
                $this->readLogoutRequestAction->setRequestParam($params)
                    ->setPostParam($postData)->execute();	}					break;
            case $this->requestParams[1]:{
                if($params['RelayState']==SPConstants::TEST_RELAYSTATE)
                {
                 $this->readResponseAction->setRequestParam($params)
                    ->setPostParam($postData)->execute(); 	}
                 $this->checkForMultipleStoreAndProceedAccordingly($params,$postData); }           break;
            case $this->requestParams[2]:
                {
                if($params['option']==SPConstants::LOGIN_ADMIN_OPT)
                    $this->adminLoginAction->execute();
                }
                break;
        }
    }

    private function setParams($request){
        $this->repostSAMLResponseRequest = $request;
		return $this;
    }

    private function setPostData($post){
        $this->repostSAMLResponsePostData = $post;
		return $this;
    }

    private function checkForMultipleStoreAndProceedAccordingly($params,$postData){
        if($this->storeManager->hasSingleStore()){
            $this->readResponseAction->setRequestParam($params)
                    ->setPostParam($postData)->execute();	
        }else{
            $dest="mospsaml/actions/spObserver";
            $get_admin_base_url = $this->spUtility->getBaseUrlWithoutStoreCode();
            $get_admin_base_url = $get_admin_base_url.$dest;
            $currentUrl = $this->spUtility->getCurrentUrl();

            $this->setParams($params);
            $this->setPostData($postData);

            $samlResponse = $this->repostSAMLResponseRequest['SAMLResponse'];
            $relayState  = array_key_exists('RelayState', $this->repostSAMLResponseRequest) ? $this->repostSAMLResponseRequest['RelayState'] : '/';		
            if($this->spUtility->isBlank($relayState)){
                $baseurl = $this->storeManager->getDefaultStoreView()->getBaseUrl();
                $observerurl = "mospsaml/actions/spObserver";
                $url = $baseurl.$observerurl;
                $customerloginUrl = "customer/account/login";
                $relayState = $baseurl.$customerloginUrl;
                $this->repostSAMLResponse($samlResponse,$relayState,$url);
                exit;
            }
            if($currentUrl == $get_admin_base_url){
                $websites = $this->storeManager->getStores();
              
                foreach($websites as $website){
                    $url = $website->getBaseUrl();
                    $pos = strpos($relayState,$url);
                    if($pos !== false){
                        $dest= "mospsaml/actions/spObserver";
                        $url = $url.$dest;
                        $this->repostSAMLResponse($samlResponse,$relayState,$url);
                    }               
                }
            }else{
                
                $this->readResponseAction->setRequestParam($params)
                    ->setPostParam($postData)->execute();	
            }
        }
    }

    private function repostSAMLResponse($samlResponse,$sendRelayState,$ssoUrl)
    {
        ob_clean();
        echo "<html><head><script src='https://code.jquery.com/jquery-1.11.3.min.js'></script><script type=\"text/javascript\">
                    $(function(){document.forms['saml-request-form'].submit();});</script></head>
                    <body>
                        <form action=\"" . $ssoUrl . "\" method=\"post\" id=\"saml-request-form\" style=\"display:none;\">
                            <input type=\"hidden\" style=\"display:none;\" name=\"SAMLResponse\" value=\"" . $samlResponse . "\" />
                            <input type=\"hidden\" style=\"display:none;\" name=\"RelayState\" value=\"" . htmlentities($sendRelayState) . "\" />
                        </form>
                        Please wait we are processing your request..
                    </body>
                </html>";
        exit();
    }
}