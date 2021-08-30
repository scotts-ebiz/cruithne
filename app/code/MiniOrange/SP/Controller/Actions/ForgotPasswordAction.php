<?php 

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Exception\AccountAlreadyExistsException;
use MiniOrange\SP\Helper\Exception\NotRegisteredException;

/**
 * Handles processing of Forgot Password Form.
 * 
 * The main function of this action class is to 
 * send a forgot password request to the user by 
 * calling the forgot_password curl.
 */
class ForgotPasswordAction extends BaseAdminAction
{
	private $REQUEST;
    
	/**
	 * Execute function to execute the classes function. 
     * 
	 * @throws \Exception
	 */
	public function execute()
	{
        $this->checkIfRequiredFieldsEmpty(array('email'=>$this->REQUEST));
        $email = $this->REQUEST['email'];
        $customerKey = $this->spUtility->getStoreConfig(SPConstants::SAMLSP_KEY);
        $apiKey = $this->spUtility->getStoreConfig(SPConstants::API_KEY);
        $content = json_decode(Curl::forgot_password($email,$customerKey,$apiKey), true);
        if(strcasecmp($content['status'], 'SUCCESS') == 0) {
            $this->messageManager->addSuccessMessage(SPMessages::PASS_RESET);
        } else {
            $this->messageManager->addErrorMessage(SPMessages::PASS_RESET_ERROR);
        }
    }


	/** Setter for the request Parameter */
    public function setRequestParam($request)
    {
		$this->REQUEST = $request;
		return $this;
    }
}