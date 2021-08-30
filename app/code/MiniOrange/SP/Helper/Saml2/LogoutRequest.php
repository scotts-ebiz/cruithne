<?php

namespace MiniOrange\SP\Helper\Saml2;

use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\Exception\MissingIDException;
use MiniOrange\SP\Helper\Exception\InvalidRequestVersionException;
use MiniOrange\SP\Helper\Exception\MissingNameIdException;
use MiniOrange\SP\Helper\Exception\InvalidNumberOfNameIDsException;


/**
 * This class is used to read the SAML logout request
 * and parse it into an object so that it's values are 
 * easily accessible. It can also be used to generate Logout
 * request from the plugin to the IDPs.
 */
class LogoutRequest
{
    private $xml;
	private $tagName;
	private $id;
	private $issuer;
	private $destination;
	private $issueInstant;
	private $certificates;
	private $validators;
    private $notOnOrAfter;
    private $encryptedNameId;
    private $nameId;
    private $sessionIndexes;
    private $bindingType;
    private $requestType =  SPConstants::LOGOUT_REQUEST;

    public function __construct(\DOMElement $xml = NULL)
    {
        $this->xml = new \DOMDocument("1.0", "utf-8");

        if ($xml === NULL) return; 

        $this->xml = $xml;
        $this->tagName = 'LogoutRequest';
        $this->id = $this->generateUniqueID(40);
        $this->issueInstant = time();
        $this->certificates = array();
        $this->validators = array();
        $this->issueInstant = SAML2Utilities::xsDateTimeToTimestamp($xml->getAttribute('IssueInstant'));

        $this->parseID($xml);
        $this->checkSAMLVersion($xml);

        if ($xml->hasAttribute('Destination')) 
            $this->destination = $xml->getAttribute('Destination');

        $this->parseIssuer($xml);
        $this->parseAndValidateSignature($xml);        

        if ($xml->hasAttribute('NotOnOrAfter')) 
            $this->notOnOrAfter = SAML2Utilities::xsDateTimeToTimestamp($xml->getAttribute('NotOnOrAfter'));

        $this->parseNameId($xml);
        $this->parseSessionIndexes($xml);
    }


    /**
     * This function is used to build our LogoutRequest. Deflate
     * and encode the LogoutRequest string if the sso binding 
     * type is empty or is of type HTTPREDIRECT.
     */
    public function build()
    {
        $requestXmlStr = $this->generateRequest();
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
     * This function is used to generate the SAML Logout Request to be
     * sent to the SPs. Currently the request is unsigned.
     */
    private function generateRequest()
    {
        //Build Logout Request
        $resp = $this->createSAMLLogoutRequest();
        $this->xml->appendChild($resp);

        //Build Issuer
        $issuer = $this->buildIssuer();
        $resp->appendChild($issuer);

        //Build NameID
        $nameId = $this->buildNameId();
        $resp->appendChild($nameId);

        //Build SessionIndex
        $sessionIndex = $this->buildSessionIndex();
        $resp->appendChild($sessionIndex);

        $samlLogoutRequest = $this->xml->saveXML();
        return $samlLogoutRequest;
    }


    /**
     * Create the SAML Logout Request Element with the appropriate attributes
     * and return it. It needs the ID , version , issueInstant and destination
     * values.
     */
    protected function createSAMLLogoutRequest()
    {
        $resp = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol','samlp:LogoutRequest');
        $resp->setAttribute('ID', $this->generateUniqueID(40));
        $resp->setAttribute('Version','2.0');
        $resp->setAttribute('IssueInstant',str_replace('+00:00','Z',gmdate("c",time())));
        $resp->setAttribute('Destination',$this->destination);
        return $resp;
    }


    /**
     * Creates the Issuer XML element of the LogoutRequest.
     */
    protected function buildIssuer()
    {
        return $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion','saml:Issuer',$this->issuer);
    }


    /**
     * Creates the NameID XML element of the LogoutRequest.
     */
    protected function buildNameId()
    {
        return $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion','saml:NameID',$this->nameId);
    }


    /**
     * Creates the SessionIndex XML element of the LogoutRequest.
     */
    protected function buildSessionIndex()
    {
        return $this->xml->createElement('samlp:SessionIndex'
                                        , is_array($this->sessionIndexes) ? $this->sessionIndexes[0] : $this->sessionIndexes);
    }


    /**
     * Parse the XML and set the ID for the request.
     *
     * @param $xml - the LogoutRequest
     * @throws MissingIDException
     */
    protected function parseID($xml)
    {
        if (!$xml->hasAttribute('ID')) 
            throw new MissingIDException();
        $this->id = $xml->getAttribute('ID');
    }


    /**
     * Check if the SAML version in the Request is 2.0
     *
     * @param $xml - the LogoutRequest
     * @throws InvalidRequestVersionException
     */
    protected function checkSAMLVersion($xml)
    {
        if ($xml->getAttribute('Version') !== '2.0') 
            throw InvalidRequestVersionException();
    } 


    /**
     * Parse the XML and set the Issuer for the request
     *
     * @param $xml - the LogoutRequest
     */
    protected function parseIssuer($xml)
    {
        $issuer = SAML2Utilities::xpQuery($xml, './saml_assertion:Issuer');
        if (!empty($issuer)) 
            $this->issuer = trim($issuer[0]->textContent);
    }


    /**
     * Parse the XML and set the SessionIndexes for the request
     *
     * @param $xml - the LogoutRequest
     */
    protected function parseSessionIndexes($xml)
    {
        $this->sessionIndexes = array();
        $sessionIndexes = SAML2Utilities::xpQuery($xml, './saml_protocol:SessionIndex');
        foreach ($sessionIndexes as $sessionIndex){
            $this->sessionIndexes[] = trim($sessionIndex->textContent);
        }
    }


    /**
     * Parse and Validate the Signature in the request
     *
     * @param $xml - the LogoutRequest
     */
    protected function parseAndValidateSignature($xml)
    {
        $sig = SAML2Utilities::validateElement($xml);
        if ($sig !== FALSE) {
            $this->certificates = $sig['Certificates'];
            $this->validators[] = array(
                'Function' => array('SAMLUtilities', 'validateSignature'),
                'Data' => $sig,
                );
        }
    }


    /**
     * Parse set the NameID for the request
     *
     * @param $xml - the LogoutRequest
     * @throws MissingNameIdException
     * @throws InvalidNumberOfNameIDsException
     */
    protected function parseNameId($xml)
    {
        $nameId = SAML2Utilities::xpQuery($xml, './saml_assertion:NameID | ./saml_assertion:EncryptedID/xenc:EncryptedData');
        if (empty($nameId)) {
            throw new MissingNameIdException();
        } elseif (count($nameId) > 1) {
            throw new InvalidNumberOfNameIDsException();
        }

        $nameId = $nameId[0];
        if ($nameId->localName === 'EncryptedData') {
            $this->encryptedNameId = $nameId;
        } else {
            $this->nameId = SAML2Utilities::parseNameId($nameId);
        }
    }


    /**
     * Convert this logout request message to an XML element.
     *
     * @return \DOMElement This logout request.
     */
    public function toUnsignedXML()
    {
        $root = toUnsignedXML();

        if ($this->notOnOrAfter !== NULL) {
            $root->setAttribute('NotOnOrAfter', gmdate('Y-m-d\TH:i:s\Z', $this->notOnOrAfter));
        }

        if ($this->encryptedNameId === NULL) {
            SAML2_Utils::addNameId($root, $this->nameId);
        } else {
            $eid = $root->ownerDocument->createElementNS(SAML2_Const::NS_SAML, 'saml:' . 'EncryptedID');
            $root->appendChild($eid);
            $eid->appendChild($root->ownerDocument->importNode($this->encryptedNameId, TRUE));
        }

        foreach ($this->sessionIndexes as $sessionIndex) {
            SAML2_Utils::addString($root, SAML2_Const::NS_SAMLP, 'SessionIndex', $sessionIndex);
        }

        return $root;
    }


    /**
     * Function is used to generate a unique ID to 
     * be used to generate unique SAML response IDs.
     *
     * @param $length a value to denote the length of unique id.
     */
    private function generateUniqueID($length)
    {
        return SAML2Utilities::generateRandomAlphanumericValue($length);
    }


    /**
     |                                          |
     | GETTER , SETTERS AND TO STRING FUNCTION  |
     |                                          |
     */

    public function __toString()
    {
        $html = 'LOGOUT REQUEST PARAMS [';
        $html .= 'TagName = '.$this->tagName;
        $html .= ', validators =  '.implode(",",$this->validators);
        $html .= ', ID = '.$this->id;
        $html .= ', Issuer = '.$this->issuer;
        $html .= ', Not On Or After = '.$this->notOnOrAfter;
        $html .= ', Destination = '.$this->destination;
        $html .= ', Encrypted NameID = '.$this->encryptedNameId;
        $html .= ', Issue Instant = '.$this->issueInstant;
        $html .= ', Session Indexes = '. implode(",",$this->sessionIndexes);
        $html .= ']';
        return $html;
    }

    /**
     * @return mixed
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * @param mixed $xml
     *
     * @return self
     */
    public function setXml($xml)
    {
        $this->xml = $xml;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * @param mixed $tagName
     *
     * @return self
     */
    public function setTagName($tagName)
    {
        $this->tagName = $tagName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIssuer()
    {
        return $this->issuer;
    }

    /**
     * @param mixed $issuer
     *
     * @return self
     */
    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param mixed $destination
     *
     * @return self
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIssueInstant()
    {
        return $this->issueInstant;
    }

    /**
     * @param mixed $issueInstant
     *
     * @return self
     */
    public function setIssueInstant($issueInstant)
    {
        $this->issueInstant = $issueInstant;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCertificates()
    {
        return $this->certificates;
    }

    /**
     * @param mixed $certificates
     *
     * @return self
     */
    public function setCertificates($certificates)
    {
        $this->certificates = $certificates;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValidators()
    {
        return $this->validators;
    }

    /**
     * @param mixed $validators
     *
     * @return self
     */
    public function setValidators($validators)
    {
        $this->validators = $validators;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNotOnOrAfter()
    {
        return $this->notOnOrAfter;
    }

    /**
     * @param mixed $notOnOrAfter
     *
     * @return self
     */
    public function setNotOnOrAfter($notOnOrAfter)
    {
        $this->notOnOrAfter = $notOnOrAfter;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEncryptedNameId()
    {
        return $this->encryptedNameId;
    }

    /**
     * @param mixed $encryptedNameId
     *
     * @return self
     */
    public function setEncryptedNameId($encryptedNameId)
    {
        $this->encryptedNameId = $encryptedNameId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNameId()
    {
        return $this->nameId;
    }

    /**
     * @param mixed $nameId
     *
     * @return self
     */
    public function setNameId($nameId)
    {
        $this->nameId = $nameId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSessionIndexes()
    {
        return $this->sessionIndexes;
    }

    /**
     * @param mixed $sessionIndexes
     *
     * @return self
     */
    public function setSessionIndexes($sessionIndexes)
    {
        $this->sessionIndexes = $sessionIndexes;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * @param mixed $requestType
     *
     * @return self
     */
    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getBindingType()
    {
        return $this->bindingType;
    }

    /**
     * @param mixed $bindingType
     *
     * @return self
     */
    public function setBindingType($bindingType)
    {
        $this->bindingType = $bindingType;

        return $this;
    }
}
