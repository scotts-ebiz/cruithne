<?php 

namespace MiniOrange\SP\Helper\Saml2;

use MiniOrange\SP\Helper\Saml2\SAML2Utilities;

/**
 * This class is primarily being used to generate the Logout Response.
 */
class LogoutResponse
{
	private $xml;
	private $id;
	private $version;
	private $destination;
	private $inResponseTo;
	private $issuer;
    private $status;
    private $bindingType;

	public function __construct($inResponseTo, $issuer, $destination, $bindingType)
    {
        $this->xml = new \DOMDocument("1.0", "utf-8");
        $this->issuer = $issuer;
        $this->destination = $destination;
        $this->inResponseTo = $inResponseTo;
        $this->bindingType = $bindingType;
    }	

    private function generateResponse()
    {
    	//Build Logout Response
        $resp = $this->createLogoutResponseElement();
        $this->xml->appendChild($resp);

        //Build Issuer
        $issuer = $this->buildIssuer();
        $resp->appendChild($issuer);

        //Build Status
        $status = $this->buildStatus();
        $resp->appendChild($status);

        $samlLogoutResponse = $this->xml->saveXML();
        return $samlLogoutResponse;
    }

    /**
     * This function is used to build our LogoutResponse. Deflate
     * and encode the LogoutResponse string if the sso binding 
     * type is empty or is of type HTTPREDIRECT.
     */
    public function build()
    {
        $requestXmlStr = $this->generateResponse();
        if(empty($this->bindingType) 
            || $this->bindingType == SPConstants::HTTP_REDIRECT) 
        {
			$deflatedStr = gzdeflate($requestXmlStr);
			$base64EncodedStr = base64_encode($deflatedStr);
			$urlEncoded = urlencode($base64EncodedStr);
			$requestXmlStr = $urlEncoded;
        }
        return $requestXmlStr;
    }

    /**
     * This function creates the SAML Logout Response Element
     * and adds the ID, version, IssueInstant, Destination and 
     * InResponseTo elements.
     */
    protected function createLogoutResponseElement()
    {
    	$resp = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol','samlp:LogoutResponse');
        $resp->setAttribute('ID', $this->generateUniqueID(40));
        $resp->setAttribute('Version','2.0');
        $resp->setAttribute('IssueInstant',str_replace('+00:00','Z',gmdate("c",time())));
        $resp->setAttribute('Destination',$this->destination);
        $resp->setAttribute('InResponseTo',$this->inResponseTo);
        return $resp;
    }


    /**
     * Creates the Issuer XML element of the LogoutResponse.
     */
    protected function buildIssuer()
    {
        return $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion','saml:Issuer',$this->issuer);
    }


    /**
     * Creates the Status XML Element
     */
    protected function buildStatus()
    {
        $statusElement = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol','samlp:Status');
        $statusElement->appendChild($this->createStatusCode());
        return $statusElement;
    }


    /**
     * Creates the StatusCode XML Element
     */
    protected function createStatusCode()
    {
        $statusCode = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol','samlp:StatusCode');
		$statusCode->setAttribute('Value', 'urn:oasis:names:tc:SAML:2.0:status:Success');
        return $statusCode;
    }


    /**
     * Function is used to generate a unique ID to 
     * be used to generate unique SAML response IDs.
     *
     * @param $length a value to denote the length of unique id.
     */
    protected function generateUniqueID($length)
    {
        return SAML2Utilities::generateRandomAlphanumericValue($length);
    }
}