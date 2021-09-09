<?php

namespace MiniOrange\SP\Observer;

use Magento\Framework\Event\ObserverInterface;
use MiniOrange\SP\Helper\SPMessages;
use Magento\Framework\Event\Observer;
use MiniOrange\SP\Controller\Actions\ReadResponseAction;
use MiniOrange\SP\Helper\SPConstants;

/**
 * This is our main logout Observer class. Observer class are used as a callback 
 * function for all of our events and hooks. This particular observer 
 * class is being used to check if the admin has initiated the logout process.
 * If so then send a logout request to the IDP.
 */
class AdminLogoutObserver implements ObserverInterface
{
	private $messageManager;
	private $logger;
	private $readResponseAction;
	private $spUtility;
	private $logoutRequestAction;

	public function __construct(\Magento\Framework\Message\ManagerInterface $messageManager,
								\Psr\Log\LoggerInterface $logger,
								\MiniOrange\SP\Controller\Actions\ReadResponseAction $readResponseAction,
								\MiniOrange\SP\Helper\SPUtility $spUtility,
								\MiniOrange\SP\Controller\Actions\SendLogoutRequest $logoutRequestAction)
    {
		//You can use dependency injection to get any class this observer may need.
		$this->messageManager = $messageManager;
		$this->logger = $logger;
		$this->readResponseAction = $readResponseAction;
		$this->spUtility = $spUtility;
		$this->logoutRequestAction = $logoutRequestAction;
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
		try{
			$userDetails = $this->spUtility->getAdminSessionData(SPConstants::USER_LOGOUT_DETAIL,TRUE);
					if($this->spUtility->isBlank($userDetails) 
				&& $this->spUtility->isUserLoggedIn())
			{	// check if user data has been set info for logout
				$data['admin'] = TRUE;
				$data['id'] =$this->spUtility->getCurrentAdminUser()->getId();
				$this->spUtility->setAdminSessionData(SPConstants::USER_LOGOUT_DETAIL,$data);
				
				return;
			}
			// send logout request if user details is not blank

			if(!$this->spUtility->isBlank($userDetails))
				$this->logoutRequestAction->setIsAdmin($userDetails['admin'])
					 ->setUserId($userDetails['id'])->execute(); 
		}catch (\Exception $e){
			$this->messageManager->addErrorMessage($e->getMessage());
			$this->logger->debug($e->getMessage());
		}
	}
}
