<?php 

namespace MiniOrange\SP\Observer;

use Magento\Framework\Event\ObserverInterface;
use MiniOrange\SP\Helper\Exception\SAMLResponseException;
use MiniOrange\SP\Helper\Exception\InvalidSignatureInResponseException;
use MiniOrange\SP\Helper\SPMessages;
use Magento\Framework\Event\Observer;
use MiniOrange\SP\Controller\Actions\ReadResponseAction;
use MiniOrange\SP\Helper\SPConstants;

class RedirectToIDPObserver implements ObserverInterface{
    
    private $requestParams = array (
        'SAMLRequest',
		'SAMLResponse',
		'option'
    );
    
    private $controllerActionPair = array (
		'account' => array('login','create'),
		'auth' => array('login'),
    );
    
    private $messageManager;
	private $logger;
	private $readResponseAction;
	private $spUtility;
	private $adminLoginAction;
	private $testAction;

	private $currentControllerName;
	private $currentActionName;
	private $readLogoutRequestAction;
	private $requestInterface;
	private $request;

	public function __construct(\Magento\Framework\Message\ManagerInterface $messageManager,
								\Psr\Log\LoggerInterface $logger,
								\MiniOrange\SP\Controller\Actions\ReadResponseAction $readResponseAction,
								\MiniOrange\SP\Helper\SPUtility $spUtility,
								\MiniOrange\SP\Controller\Actions\AdminLoginAction $adminLoginAction,
								\Magento\Framework\App\Request\Http $httpRequest,
								\MiniOrange\SP\Controller\Actions\ReadLogoutRequestAction $readLogoutRequestAction,
								\Magento\Framework\App\RequestInterface $request,
								\MiniOrange\SP\Controller\Actions\ShowTestResultsAction $testAction)
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
    }

    	/**
	 * This function is called as soon as the observer class is initialized.
	 * Checks if the request parameter has any of the configured request 
	 * parameters and handles any exception that the system might throw.
	 * 
	 * @param $observer
	 */
    public function execute(Observer $observer)
    {		
		$keys 			= array_keys($this->request->getParams());
		$operation 		= array_intersect($keys,$this->requestParams);
		try{
			if($this->checkIfUserShouldBeRedirected())
			{	//redirecting to the loginrequest controller
				$observer->getControllerAction()->getResponse()
						 ->setRedirect($this->spUtility->getSPInitiatedUrl());
			}
		}catch (\Exception $e){
			if($isTest) { // show a failed validation screen
				$this->testAction->setSamlException($e)->setHasExceptionOccurred(TRUE)->execute();
			}
			$this->messageManager->addErrorMessage($e->getMessage());
			$this->logger->debug($e->getMessage());
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

}