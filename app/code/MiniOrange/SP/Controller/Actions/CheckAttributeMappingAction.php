<?php 

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Exception\MissingAttributesException;
use MiniOrange\SP\Helper\SPConstants;

/**
 * This class handles checking of the SAML attributes and NameID 
 * coming in the response and mapping it to the attribute mapping 
 * done in the plugin settings by the admin to update the user.
 */
class CheckAttributeMappingAction extends BaseAction
{
    const TEST_VALIDATE_RELAYSTATE = SPConstants::TEST_RELAYSTATE;

    private $samlResponse;
    private $relayState;
    private $emailAttribute;
    private $usernameAttribute;
    private $firstName;
    private $lastName;
    private $checkIfMatchBy;
    private $groupName;

    private $testAction;
    private $processUserAction;

    public function __construct(\Magento\Backend\App\Action\Context $context,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \MiniOrange\SP\Controller\Actions\ShowTestResultsAction $testAction,
                                \MiniOrange\SP\Controller\Actions\ProcessUserAction $processUserAction)
	{
        //You can use dependency injection to get any class this observer may need.
        $this->emailAttribute = $spUtility->getStoreConfig(SPConstants::MAP_EMAIL);
        $this->emailAttribute = $spUtility->isBlank($this->emailAttribute) ? SPConstants::DEFAULT_MAP_EMAIL : $this->emailAttribute;
        $this->usernameAttribute = $spUtility->getStoreConfig(SPConstants::MAP_USERNAME);
        $this->usernameAttribute = $spUtility->isBlank($this->usernameAttribute) ? SPConstants::DEFAULT_MAP_USERN : $this->usernameAttribute;
        $this->firstName = $spUtility->getStoreConfig(SPConstants::MAP_FIRSTNAME);
        $this->firstName = $spUtility->isBlank($this->firstName) ? SPConstants::DEFAULT_MAP_FN : $this->firstName;
        $this->lastName = $spUtility->getStoreConfig(SPConstants::MAP_LASTNAME);
        $this->checkIfMatchBy = $spUtility->getStoreConfig(SPConstants::MAP_MAP_BY);
        $this->groupName = $spUtility->getStoreConfig(SPConstants::MAP_GROUP);
        $this->testAction = $testAction;
        $this->processUserAction = $processUserAction;
		parent::__construct($context,$spUtility);
	}

	/**
	 * Execute function to execute the classes function. 
	 */
	public function execute()
	{
		$ssoemail = current(current($this->samlResponse->getAssertions())->getNameId());
		$attrs = current($this->samlResponse->getAssertions())->getAttributes();
		$attrs['NameID'] = array($ssoemail);
        $sessionIndex = current($this->samlResponse->getAssertions())->getSessionIndex();
        $this->moSAMLcheckMapping($attrs,$sessionIndex);
    }


    /**
     * This function checks the SAML Attribute Mapping done
     * in the plugin and matches it to update the user's
     * attributes.
     *
     * @param $attrs
     * @param $sessionIndex
     * @throws MissingAttributesException;
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function moSAMLcheckMapping($attrs,$sessionIndex)
    {
        if(empty($attrs)) throw new MissingAttributesException;
        if($this->spUtility->isBlank($this->checkIfMatchBy)) $this->checkIfMatchBy = SPConstants::DEFAULT_MAP_BY;
        $this->processFirstName($attrs);
        $this->processUserName($attrs);
        $this->processEmail($attrs);
        $this->processGroupName($attrs);
        $this->processResult($attrs,$sessionIndex,$attrs['NameID']);
    }


    /**
     * Process the result to either show a Test result
     * screen or log/create user in Magento.
     *
     * @param $attrs
     * @param $sessionIndex
     * @param $nameId
     * @throws MissingAttributesException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processResult($attrs,$sessionIndex,$nameId)
    {
        switch($this->relayState)
        {
            case self::TEST_VALIDATE_RELAYSTATE :
                $this->testAction->setAttrs($attrs)->setNameId($nameId[0])->execute();          break;
            default:
                $this->processUserAction->setAttrs($attrs)->setRelayState($this->relayState)
                    ->setSessionIndex($sessionIndex)->execute();                                break;
        }
    }


    /**
     * Check if the attribute list has a FirstName. If 
     * no firstName is found then NameID is considered as
     * the firstName. This is done because Magento needs
     * a firstName for creating a new user.
     * 
     * @param $attrs
     */
    private function processFirstName(&$attrs)
    {
        if(!isset($attrs[$this->firstName]))
            $attrs[$this->firstName][0] = $attrs['NameID'][0];
    }


    /**
     * Check if the attribute list has a UserName. If 
     * no UserName is found then NameID is considered as
     * the UserName. This is done because Magento needs
     * a UserName for creating a new user.
     * 
     * @param $attrs
     */
    private function processUserName(&$attrs)
    {
        if(!isset($attrs[$this->usernameAttribute]))
            $attrs[$this->usernameAttribute][0] 
                = $this->checkIfMatchBy==SPConstants::DEFAULT_MAP_USERN ? $attrs['NameID'][0] : null;
    }


    /**
     * Check if the attribute list has a Email. If 
     * no Email is found then NameID is considered as
     * the Email. This is done because Magento needs
     * a Email for creating a new user.
     * 
     * @param $attrs
     */
    private function processEmail(&$attrs)
    {
        if(!isset($attrs[$this->emailAttribute]))
            $attrs[$this->emailAttribute][0] 
                = $this->checkIfMatchBy==SPConstants::DEFAULT_MAP_EMAIL ? $attrs['NameID'][0] : null;
    }


    /**
     * Check if the attribute list has a Group/Role. If 
     * no Group/Role is found then NameID is considered as
     * the Group/Role. This is done because Magento needs
     * a Group/Role for creating a new user.
     * 
     * @param $attrs
     */
    private function processGroupName(&$attrs)
    {
        if(!isset($attrs[$this->groupName]))
            $this->groupName = array();
    }
    

    /** Setter for the SAML Response Parameter */
    public function setSamlResponse($samlResponse)
    {
        $this->samlResponse = $samlResponse;
        return $this;
    }


    /** Setter for the RelayState Parameter */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
        return $this;
    }  
}