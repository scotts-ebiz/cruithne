<?php 

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Saml2\LogoutResponse;
use MiniOrange\SP\Helper\SPConstants;

/**
 * Handles generation and sending of LogoutRequest to the IDP
 * for processing. LogoutRequest is generated based on the ID
 * set in the observer. NameId is fetched and sent in the logout
 * request based on if the user is admin or customer. 
 */
class SendLogoutResponse extends BaseAction
{
    private $isAdmin;
    private $userId;
    private $requestId;

	/**
	 * Execute function to execute the classes function. 
	 * @throws NotRegisteredException
	 * @throws \Exception
	 */
	public function execute()
	{
		$this->checkIfValidPlugin();
        if(!$this->spUtility->isSPConfigured() || !$this->spUtility->isSLOConfigured()) return;
        //get required values from the database
        $destination = $this->spUtility->getStoreConfig(SPConstants::SAML_SLO_URL);
        $bindingType = $this->spUtility->getStoreConfig(SPConstants::LOGOUT_BINDING);
        $sendRelayState = $this->isAdmin ? $this->spUtility->getAdminBaseUrl() : $this->spUtility->getBaseUrl();
        $issuer = $this->spUtility->getIssuerUrl();
        //generate the logout request
        $logoutResponse = (new LogoutResponse($this->requestId,$issuer,$destination,$bindingType))->build();
        // send saml request over
        if(empty($bindingType) 
            || $bindingType == SPConstants::HTTP_REDIRECT)
            return $this->sendHTTPRedirectResponse($logoutResponse,$sendRelayState,$destination);
        else
            $this->sendHTTPPostResponse($logoutResponse,$sendRelayState,$destination);
    }

    /** The setter function for the request Id Parameter */
    public function setRequestId($id)
    {
        $this->requestId = $id;
        return $this;
    }
}