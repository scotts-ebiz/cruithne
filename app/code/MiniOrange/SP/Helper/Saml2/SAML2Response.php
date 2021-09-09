<?php
/**
 * This file is part of miniOrange SAML plugin.
 *
 * miniOrange SAML plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * miniOrange SAML plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with miniOrange SAML plugin.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace MiniOrange\SP\Helper\Saml2;

use MiniOrange\SP\Helper\Saml2\SAML2Assertion;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use DOMDocument;
use DOMElement;

/**
 * Class for SAML2 Response messages.
 * @todo - This class needs to be modified and optimized. 
 */
class SAML2Response
{
    private $assertions;
	private $destination;
	private $certificates;
    private $signatureData;
    private $spUtility;
    private $statusCode;
    private $xml;
    public $ownerDocument;
    
	/**
     * Constructor for SAML 2 response messages.
     *
     * @param DOMElement|NULL $xml The input message.
     */
    public function __construct(\DOMElement $xml = NULL,\MiniOrange\SP\Helper\SPUtility $spUtility)
    {
        $this->assertions = array();
        $this->certificates = array();
        $this->spUtility = $spUtility;
        $this->xml = $xml;
        $this->ownerDocument = $xml->ownerDocument;
        if ($xml === null) {
            return;
        }

        // if ($xml === NULL) return;
		
		$sig = SAML2Utilities::validateElement($xml);
        if ($sig !== FALSE) 
        {
			$this->certificates = $sig['Certificates'];
			$this->signatureData = $sig;
        }
        
        $doc = $xml->ownerDocument;

        $xpath = new \DOMXpath($doc);

        if(!(@$xpath->query('/saml2p:Response',$xml))){
            $status = SAML2Utilities::xpQuery($xml, './samlp:Status/samlp:StatusCode');
        }
        else{
           $status = SAML2Utilities::xpQuery($xml, './saml2p:Status/saml2p:StatusCode');
        }

        // $status = SAML2Utilities::xpQuery($xml, './samlp:Status/samlp:StatusCode');
        $this->statusCode = $status[0]->getAttribute('Value');
		
		/* set the destination from saml response */
        if ($xml->hasAttribute('Destination')) 
            $this->destination = $xml->getAttribute('Destination');
		
        for ($node = $xml->firstChild; $node !== NULL; $node = $node->nextSibling) 
        {
            if ($node->namespaceURI !== 'urn:oasis:names:tc:SAML:2.0:assertion') 
                continue;
            if ($node->localName === 'Assertion' || $node->localName === 'EncryptedAssertion')	
                $this->assertions[] = new SAML2Assertion($node,$this->spUtility);
		}
    }

    /** Convert the response message to an XML element.   */
    public function toUnsignedXML()
    {
        $root = toUnsignedXML();
        foreach ($this->assertions as $assertion) {
            $assertion->toXML($root);
        }
        return $root;
    }

    /** Retrieve the assertions in this response.  */
    public function getAssertions()
    {	
        return $this->assertions;
    }

    /** Set the assertions that should be included in this response. */
    public function setAssertions(array $assertions)
    {
        $this->assertions = $assertions;
    }
	
	public function getDestination()
    {
        return $this->destination;
    }

	public function getCertificates()
	{
		return $this->certificates;
	}

	public function getSignatureData()
	{
		return $this->signatureData;
    }
    
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getXML()
    {
        return $this->xml;
    }
}
