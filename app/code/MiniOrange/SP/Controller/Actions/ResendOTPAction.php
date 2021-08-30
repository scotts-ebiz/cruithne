<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Exception\OTPSendingFailedException;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * Handles processing of the Resend OTP to Phone/Email form from the 
 * validation page. This action checks the session of what type of 
 * validation is the user trying to do and resend the otp to
 * that authentication type.
 */
class ResendOTPAction extends BaseAdminAction
{
    private $REQUEST;

    /**
	 * Execute function to execute the classes function. 
     * 
	 * @throws \Exception
	 */
	public function execute()
	{
        // fetch the last place otp was sent to - phone or email
        $otpType = $this->spUtility->getStoreConfig(SPConstants::OTP_TYPE); 
        $email = $this->spUtility->getStoreConfig(SPConstants::SAMLSP_EMAIL);
        $phone = $this->spUtility->getStoreConfig(SPConstants::SAMLSP_PHONE);
        $this->startVerificationProcess($phone,$email,$otpType);
    }



    /**
     * Function calls the Curl function to send the OTP 
     * to the phone number or email address provided by the admin.
     * 
     * @param $phone
     * @param $email
     * @param $otpType
     */
    private function startVerificationProcess($phone,$email,$otpType)
    {
        $result = Curl::mo_send_otp_token($otpType,$email,$phone);
        $result = json_decode($result,true);
        if(strcasecmp($result['status'], 'SUCCESS') == 0)
            $this->handleOTPSentSuccess($result,$phone,$email,$otpType);
        else
            $this->handleOTPSendFailed();
    }


    /**
     * This function is called to handle what should happen
     * after OTP has been sent successfully to the user's 
     * phone or email. Show him the validate OTP screen.
     * Set the Transaction ID and otpType in session so
     * that it can fetched later on.
     * 
     * @param $result
     * @param $phone
     * @param $email
     * @param $otpType
     */
    private function handleOTPSentSuccess($result,$phone,$email,$otpType)
    {
        $this->spUtility->setStoreConfig(SPConstants::TXT_ID,$result['txId']);
        $this->spUtility->setStoreConfig(SPConstants::OTP_TYPE,$otpType);
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS,SPConstants::STATUS_VERIFY_EMAIL);
        $message = $otpType==SPConstants::OTP_TYPE_PHONE ? SPMessages::parse('PHONE_OTP_SENT',array('phone'=>$phone))
                                                         : SPMessages::parse('EMAIL_OTP_SENT',array('email'=>$email));
        $this->messageManager->addSuccessMessage($message);
    }


     /**
     * This function is called to handle what should happen
     * after sending of OTP fails for a phone number or email.
     */
    private function handleOTPSendFailed()
    {
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS,SPConstants::STATUS_VERIFY_EMAIL);
        throw new OTPSendingFailedException;
    }


    /** Setter for the request Parameter */
    public function setRequestParam($request)
    {
		$this->REQUEST = $request;
		return $this;
    }
}