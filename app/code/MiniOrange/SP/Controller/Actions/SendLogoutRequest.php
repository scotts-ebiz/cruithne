<?php 

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Saml2\LogoutRequest;
use MiniOrange\SP\Helper\SPConstants;

/**
 * Handles generation and sending of LogoutRequest to the IDP
 * for processing. LogoutRequest is generated based on the ID
 * set in the observer. NameId is fetched and sent in the logout
 * request based on if the user is admin or customer. 
 */
class SendLogoutRequest extends BaseAction
{
    private $isAdmin;
    private $userId;
    private $nameID;
    private $sessionIndex;

	/**
	 * Execute function to execute the classes function. 
	 * @throws NotRegisteredException
	 * @throws \Exception
	 */
	public function execute()
	{
	
	$this->checkIfValidPlugin();
       
	if(!$this->spUtility->isSPConfigured() || !$this->spUtility->isSLOConfigured()) {
	
	 return;
	}
        //get required values from the database
 
        $destination = $this->spUtility->getStoreConfig(SPConstants::SAML_SLO_URL);
        $bindingType = $this->spUtility->getStoreConfig(SPConstants::LOGOUT_BINDING);

	$nameId = $this->nameID;
	$sessionIndex = $this->sessionIndex;

	$data = $this->isAdmin ? $this->spUtility->getAdminStoreConfig('extra',$this->userId)
                                 : $this->spUtility->getCustomerStoreConfig('extra',$this->userId);

	$nameId = $data['NameID'];
	$sessionIndex = $data['SessionIndex'];
        $sendRelayState = $this->isAdmin ? $this->spUtility->getAdminBaseUrl() : $this->spUtility->getBaseUrl();
        $issuer = $this->spUtility->getIssuerUrl();        
        //remove nameid and session index for user
        $this->spUtility->saveConfig('extra','',$this->userId,$this->isAdmin);
        
        //generate the logout request

        $logoutRequest = (new LogoutRequest())->setIssuer($issuer)->setDestination($destination)
                                              ->setNameId($nameId)->setSessionIndexes($sessionIndex)
                                              ->setBindingType($bindingType)->build();
        // send saml request over
        if(empty($bindingType) || $bindingType == SPConstants::HTTP_REDIRECT) {
           
	     return $this->sendHTTPRedirectRequest($logoutRequest,$sendRelayState,$destination);
	}       
 	else {
            $this->sendHTTPPostRequest($logoutRequest,$sendRelayState,$destination);
	}
    }


    /** The setter function for the isAdmin Parameter */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }


    /** The setter function for the userId Parameter */
    public function setuserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

   



}
