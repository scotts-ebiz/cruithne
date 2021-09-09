<?php 

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Saml2\LogoutRequest;
use MiniOrange\SP\Helper\SPConstants;

/**
 * Handles reading of SAML Logout Request from the IDP. Read the SAML Request 
 * from the IDP and process it to detect if it's a valid logout Request.
 * Generate a SAML Logout Response Object and logs the user out.
 */
class ReadLogoutRequestAction extends BaseAction
{
	private $REQUEST;
	private $POST;

	/**
	 * Execute function to execute the classes function. 
	 * @throws NotRegisteredException
     * @throws MissingIDException;
     * @throws InvalidRequestVersionException;
     * @throws MissingNameIdException;
     * @throws InvalidNumberOfNameIDsException;
	 * @throws \Exception
	 */
	public function execute()
	{
		$this->checkIfValidPlugin();
		// read the request
        $samlRequest = $this->REQUEST['SAMLRequest'];
        $relayState  = isset($this->REQUEST['RelayState']) ? $this->REQUEST['RelayState'] : '';
        $samlRequest = base64_decode($samlRequest);
        if(!isset($this->POST['SAMLRequest'])) {
			$samlRequest = gzinflate($samlRequest);
        }
        $document = new \DOMDocument();
		$document->loadXML($samlRequest);
        $samlRequestXML = $document->firstChild;
        if( $samlRequestXML->localName == 'LogoutRequest' )
        {            
            $logoutRequest = new LogoutRequest( $samlRequestXML );        
            return $this->logoutUser($logoutRequest,$relayState);
        }
    }
    

    /**
     * Function checks if there's an active session of the user in the
     * frontend or backend and redirect the user to the appropriate 
     * logout URL for sending SAML Logout Response.
     * 
     * @param $logoutRequest
     * @param $relayState
     */
    private function logoutUser($logoutRequest,$relayState)
    {
        $this->spUtility->setSessionDataForCurrentUser(SPConstants::SEND_RESPONSE,TRUE);
        $this->spUtility->setSessionDataForCurrentUser(SPConstants::LOGOUT_REQUEST_ID,$logoutRequest->getId());
        // redirect the user to appropriate logout url   
        return $this->resultRedirectFactory->create()->setUrl($this->spUtility->getLogoutUrl());     
    }


	/** Setter for the request Parameter */
    public function setRequestParam($request)
    {
		$this->REQUEST = $request;
		return $this;
    }


    /** Setter for the post Parameter */
    public function setPostParam($post)
    {
		$this->POST = $post;
		return $this;
    } 
}