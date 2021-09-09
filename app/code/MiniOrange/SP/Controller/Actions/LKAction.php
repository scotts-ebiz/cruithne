<?php 

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Saml2\Lib\AESEncryption;

/**
 * Handles processing of customer verify license key form. 
 * Checks if the license key entered by the user is a valid
 * license key for his account or not. If so then activate 
 * his license.
 * 
 * The main function of this action class is to authenticate
 * the user credentials as provided by calling an API and 
 * fetching all of the relevant information of the customer.
 * Store the key, token and email in the database.
 */
class LKAction extends BaseAdminAction
{
	private $REQUEST;

    /**
	 * Execute function to execute the classes function. 
     * Handles the removing the configured license and customer
     * account from the module by removing the necessary keys
     * and feeing the key.
     * 
	 * @throws \Exception
	 */
	public function removeAccount()
	{
        if($this->spUtility->micr()) {
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL,'');
			$this->spUtility->setStoreConfig(SPConstants::SAMLSP_KEY,'');
			$this->spUtility->setStoreConfig(SPConstants::API_KEY,'');
            $this->spUtility->setStoreConfig(SPConstants::TOKEN,'');
            $this->spUtility->setStoreConfig(SPConstants::REG_STATUS,SPConstants::STATUS_VERIFY_LOGIN);
		}
		
		/*  ===================================================================================================
							THE CODE BELOW IS SPECIFIC TO THE PREMIUM PLUGIN ONLY
			===================================================================================================
		*/
		
		if($this->spUtility->mclv()) {
            $this->spUtility->mius();
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_LK, '');
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_CKL, '');
        }
	}

	/** Setter for the request Parameter */
    public function setRequestParam($request)
    {
		$this->REQUEST = $request;
		return $this;
    }
	
	/* ===================================================================================================
			THE FUNCTIONS BELOW ARE PREMIUM PLUGIN SPECIFIC AND DIFFER IN THE FREE VERSION
		===================================================================================================
	*/

	/**
	 * Execute function to execute the classes function. 
     * Handles the license key verify form. Takes the 
	 * license key given by the Admin and send it to server
	 * for  validation.
     * 
	 * @throws \Exception
	 */
	public function execute()
	{
        $this->checkIfRequiredFieldsEmpty(array('lk'=>$this->REQUEST));
        $lk = $this->REQUEST['lk'];
        $result = json_decode($this->spUtility->ccl(), true);
        switch ($result['status'])
		{
			case 'SUCCESS':
				$this->_vlk_success($lk);		break;
			default:
				$this->_vlk_fail();				break;
		}
    }

	/* ===================================================================================================
							THE FUNCTIONS BELOW ARE ONLY FOR THE PREMIUM PLUGIN
		===================================================================================================
	*/

    /**
	 * Handles the steps to take on successful 
	 * validation of the license key entered by the Admin.
	 *
	 * @param $code refers to the code entered by the Admin which has been verified
	 * @param $usersCount refers to the number of users puchsed by Admin
	 */
	public function _vlk_success($code)
	{
	    $content = json_decode($this->spUtility->vml($code),true);
		if(strcasecmp($content['status'], 'SUCCESS') == 0)
		{
            $key = $this->spUtility->getStoreConfig(SPConstants::TOKEN);
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_LK, AESEncryption::encrypt_data($code,$key));
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_CKL, AESEncryption::encrypt_data("true",$key));
            /** @todo:  run scheduler here for every 3 day check */
			$this->messageManager->addSuccessMessage(SPMessages::LICENSE_VERIFIED);
		}
		else if(strcasecmp($content['status'], 'FAILED') == 0)
		{
			if(strcasecmp($content['message'], 'Code has Expired') == 0)
                $this->messageManager->addErrorMessage(SPMessages::LICENSE_KEY_IN_USE);
			else
                $this->messageManager->addErrorMessage(SPMessages::ENTERED_INVALID_KEY);
		}
		else
        {
            $this->messageManager->addErrorMessage(SPMessages::ERROR_OCCURRED);
	    }
	}


	/**
	 * Handles the steps to take on un-successful 
	 * validation of the license key entered by the Admin.
	 */
	public function _vlk_fail()
	{
		$key = $this->spUtility->getStoreConfig(SPConstants::TOKEN);
		$this->spUtility->setStoreConfig(SPConstants::SAMLSP_CKL, AESEncryption::encrypt_data("false",$key));
        $this->messageManager->addErrorMessage(SPMessages::NOT_UPGRADED_YET);
	}
}