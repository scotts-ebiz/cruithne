<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Exception\NotRegisteredException;
use MiniOrange\SP\Helper\Saml2\AuthnRequest;
use MiniOrange\SP\Helper\SPConstants;

/**
 * Handles generation and sending of AuthnRequest to the IDP
 * for authentication. AuthnRequest is generated and user is
 * redirected to the IDP for authentication.
 */
class SendAuthnRequest extends BaseAction
{

    /**
     * Execute function to execute the classes function.
     * @throws \Exception
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();  //get params
        if (!$this->spUtility->isSPConfigured()) {
            return;
        }
        $relayState = array_key_exists('relayState', $params) ? $params['relayState'] : '/';
        
        //get required values from the database
        $ssoUrl = $this->spUtility->getStoreConfig(SPConstants::SAML_SSO_URL);
        $bindingType =  $this->spUtility->getStoreConfig(SPConstants::BINDING_TYPE);
        $forceAuthn = $this->spUtility->getStoreConfig(SPConstants::FORCE_AUTHN);
        $acsUrl = $this->spUtility->getAcsUrl();
        $issuer = $this->spUtility->getIssuerUrl();
        //generate the saml request
        $samlRequest = (new AuthnRequest($acsUrl, $issuer, $ssoUrl, $forceAuthn, $bindingType))->build();
        // send saml request over
        if (empty($bindingType)
            || $bindingType == SPConstants::HTTP_REDIRECT) {
            return $this->sendHTTPRedirectRequest($samlRequest, $relayState, $ssoUrl);
        } else {
            $this->sendHTTPPostRequest($samlRequest, $relayState, $ssoUrl);
        }
    }
}
