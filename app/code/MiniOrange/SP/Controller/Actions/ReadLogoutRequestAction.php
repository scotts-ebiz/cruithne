<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Saml2\LogoutRequest;
use MiniOrange\SP\Helper\SPZendUtility;

/**
 * Handles reading of SAML Logout Request from the IDP. Read the SAML Request
 * from the IDP and process it to detect if it's a valid logout Request.
 * Generate a SAML Logout Response Object and logs the user out.
 */
class ReadLogoutRequestAction extends BaseAction
{
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

        // read the request
        $samlRequest = $this->REQUEST['SAMLRequest'];
        $relayState  = array_key_exists('RelayState', $this->REQUEST) ? $this->REQUEST['RelayState'] : '/';
        $samlRequest = SPZendUtility::base64Decode($samlRequest);
        if (!array_key_exists('SAMLRequest', $this->POST)) {
            $samlRequest = SPZendUtility::gzInflate($samlRequest);
        }
        $document = new \DOMDocument();
        $document->loadXML($samlRequest);
        $samlRequestXML = $document->firstChild;
        if ($samlRequestXML->localName == 'LogoutRequest') {
            return;
        }
    }
}
