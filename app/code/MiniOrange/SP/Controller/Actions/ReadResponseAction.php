<?php 

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Saml2\SAML2Response;

/**
 * Handles reading of SAML Responses from the IDP. Read the SAML Response 
 * from the IDP and process it to detect if it's a valid response from the IDP.
 * Generate a SAML Response Object and log the user in. Update existing user
 * attributes and groups if necessary.
 */
class ReadResponseAction extends BaseAction
{
	private $REQUEST;
	private $POST;
	private $processResponseAction;

	public function __construct(\Magento\Backend\App\Action\Context $context,
								\MiniOrange\SP\Helper\SPUtility $spUtility,
								\MiniOrange\SP\Controller\Actions\ProcessResponseAction $processResponseAction)
	{
		//You can use dependency injection to get any class this observer may need.
		$this->processResponseAction = $processResponseAction;
		parent::__construct($context,$spUtility);
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
		$this->checkIfValidPlugin();
		// read the response
		$samlResponse = $this->REQUEST['SAMLResponse'];
		$relayState  = isset($this->REQUEST['RelayState']) ? $this->REQUEST['RelayState'] : '/';		
		//decode the saml response
		$samlResponse = base64_decode($samlResponse);
		if(!isset($this->POST['SAMLResponse'])) {
			$samlResponse = gzinflate($samlResponse);
		}
		
		$document = new \DOMDocument();
		$document->loadXML($samlResponse);
		$samlResponseXML = $document->firstChild;
		//if logout response then redirect the user to the relayState

		if($samlResponseXML->localName == 'LogoutResponse') {

			header('Location:'. $relayState);
			exit;
		}
		$samlResponse = new SAML2Response($samlResponseXML,$this->spUtility);	//convert the xml to SAML2Response object
		$this->processResponseAction->setSamlResponse($samlResponse)
			->setRelayState($relayState)->execute();
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
