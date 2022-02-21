<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Saml2\SAML2Response;
use MiniOrange\SP\Helper\SPZendUtility;

/**
 * Handles reading of SAML Responses from the IDP. Read the SAML Response
 * from the IDP and process it to detect if it's a valid response from the IDP.
 * Generate a SAML Response Object and log the user in. Update existing user
 * attributes and groups if necessary.
 */
class ReadResponseAction extends BaseAction
{
    private $processResponseAction;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \MiniOrange\SP\Controller\Actions\ProcessResponseAction $processResponseAction
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->processResponseAction = $processResponseAction;
        parent::__construct($context, $spUtility);
    }

    /**
     * Execute function to execute the classes function.
     * @throws NotRegisteredException
     * @throws InvalidSAMLVersionException
     * @throws MissingIDException
     * @throws MissingIssuerValueException
     * @throws MissingNameIdException
     * @throws InvalidNumberOfNameIDsException
     * @throws \Exception
     */
    public function execute()
    {

        // read the response
        $samlResponse = $this->REQUEST['SAMLResponse'];
        $relayState  = array_key_exists('RelayState', $this->REQUEST) ? $this->REQUEST['RelayState'] : '/';
        //decode the saml response
        $samlResponse = SPZendUtility::base64Decode($samlResponse);
        if (!array_key_exists('SAMLResponse', $this->POST)) {
            $samlResponse = SPZendUtility::gzInflate($samlResponse);
        }
        
        $document = new \DOMDocument();
        $document->loadXML($samlResponse);
        $samlResponseXML = $document->firstChild;
        //if logout response then redirect the user to the relayState
        if ($samlResponseXML->localName == 'LogoutResponse') {
            return $this->resultRedirectFactory->create()->setUrl($relayState);
        }
        
        $samlResponse = new SAML2Response($samlResponseXML, $this->spUtility);    //convert the xml to SAML2Response object
        return $this->processResponseAction->setSamlResponse($samlResponse)
            ->setRelayState($relayState)->execute();
    }
}
