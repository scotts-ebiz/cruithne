<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Exception\OTPSendingFailedException;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * Handles processing of the Send OTP to Phone form from the
 * validation page. This action class just processes the phone
 * number provided by the user as an alternative to validate
 * himself for the registration process.
 */
class SendOTPToPhone extends BaseAdminAction
{
    /**
     * Execute function to execute the classes function.
     *
     * @throws \Exception
     */
    public function execute()
    {
        $this->checkIfRequiredFieldsEmpty(['phone'=>$this->REQUEST]);
        $phone = $this->REQUEST['phone'];
        $this->startVerificationProcess('', $phone);
    }


    /**
     * Function calls the Curl function to send the OTP
     * to the phone number provided by the admin.
     *
     * @param $result
     * @param $phone
     * @throws OTPSendingFailedException
     */
    private function startVerificationProcess($result, $phone)
    {
        $result = Curl::mo_send_otp_token(SPConstants::OTP_TYPE_PHONE, '', $phone);
        $result = json_decode($result, true);
        if (strcasecmp($result['status'], 'SUCCESS') == 0) {
            $this->handleOTPSentSuccess($result, $phone);
        } else {
            $this->handleOTPSendFailed();
        }
    }


    /**
     * This function is called to handle what should happen
     * after OTP has been sent successfully to the user's
     * phone. Show him the validate OTP screen.
     * Set the Transaction ID and otpType in session so
     * that it can fetched later on.
     *
     * @param $result
     * @param $phone
     */
    private function handleOTPSentSuccess($result, $phone)
    {
        $this->spUtility->setStoreConfig(SPConstants::TXT_ID, $result['txId']);
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_PHONE, $phone); // for resend otp purposes
        $this->spUtility->setStoreConfig(SPConstants::OTP_TYPE, SPConstants::OTP_TYPE_PHONE);
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_VERIFY_EMAIL);
        $this->getMessageManager()->addSuccessMessage(SPMessages::parse('PHONE_OTP_SENT', ['phone'=>$phone]));
        
    }


     /**
      * This function is called to handle what should happen
      * after sending of OTP fails for a phone number.
      */
    private function handleOTPSendFailed()
    {
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_VERIFY_EMAIL);
        throw new OTPSendingFailedException;
    }
}
