<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Exception\AccountAlreadyExistsException;
use MiniOrange\SP\Helper\Exception\OTPValidationFailedException;
use MiniOrange\SP\Helper\Exception\RequiredFieldsException;
use MiniOrange\SP\Helper\Exception\OTPRequiredException;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * Handles processing of the validate OTP form. Takes the OTP
 * entered by the user and sends it for validation. If validation
 * is successful then register him in the plugin otherwise
 * throw an error.
 */
class ValidateOTPAction extends BaseAdminAction
{
    /**
     * Execute function to execute the classes function.
     *
     * @throws \Exception
     */
    public function execute()
    {
        $this->checkIfRequiredFieldsEmpty(['submit'=>$this->REQUEST]);
        $submit = $this->REQUEST['submit'];
        $txID = $this->spUtility->getStoreConfig(SPConstants::TXT_ID);
        $otp = $this->REQUEST['otp_token'];
        if ($submit=="Back") {
            $this->goBackToRegistrationPage();
        } else {
            $this->validateOTP($txID, $otp);
        }
    }


    /**
     * Function resets all the values in the database and sends
     * the user back to the registration page for a fresh
     * activation of the plugin.
     */
    private function goBackToRegistrationPage()
    {
        $this->spUtility->setStoreConfig(SPConstants::OTP_TYPE, '');
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL, '');
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_PHONE, '');
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, '');
        $this->spUtility->setStoreConfig(SPConstants::TXT_ID, '');
    }


    /**
     * Function calls the Curl function to validate the OTP
     * entered by the admin.
     */
    private function validateOTP($transactionID, $otpToken)
    {
        if (!array_key_exists('otp_token', $this->REQUEST)
            || $this->REQUEST['otp_token']=="") {
            throw new OTPRequiredException;
        }
        $result = Curl::validate_otp_token($transactionID, $otpToken);
        $result = json_decode($result, true);
        if (strcasecmp($result['status'], 'SUCCESS') == 0) {
            $this->handleOTPValidationSuccess($result);
        } else {
            $this->handleOTPValidationFailed();
        }
    }


    /**
     * This function handles what should happen after successful
     * validation of the OTP entered by the admin. Call the create customer
     * API to create user in miniOrange and fetch user customerKey, apiKey, etc.
     *
     * @param $result
     * @throws AccountAlreadyExistsException
     */
    private function handleOTPValidationSuccess($result)
    {
        $companyName = $this->spUtility->getStoreConfig(SPConstants::SAMLSP_CNAME);
        $firstName = $this->spUtility->getStoreConfig(SPConstants::SAMLSP_FIRSTNAME);
        $lastName = $this->spUtility->getStoreConfig(SPConstants::SAMLSP_LASTNAME);
        $email = $this->spUtility->getStoreConfig(SPConstants::SAMLSP_EMAIL);
        $phone = $this->spUtility->getStoreConfig(SPConstants::SAMLSP_PHONE);
        $regStatus= $this->spUtility->getStoreConfig(SPConstants::REG_STATUS);
        $pass=$this->spUtility->getAdminSession()->getTempKey() ;

        if (!isset($email) || !isset($pass) || (strpos($regStatus, 'VALIDATE')!==false)) {
            $result = Curl::create_customer($email, $companyName, '', $phone, $firstName, $lastName);
            $this->spUtility->getAdminSession()->unsTempKey() ;
            $result = json_decode($result, true);
            if (strcasecmp($result['status'], 'SUCCESS') == 0) {
                $this->configureUserInMagento($result);
            } elseif (strcasecmp($result['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0) {
                $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_VERIFY_LOGIN);
                throw new AccountAlreadyExistsException;
            }
        } else {
            $this->getMessageManager()->addErrorMessage(SPMessages::INVALID_REG);
            $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, "");

        }
    }


    /**
     * After user is created in miniOrange store relevant information
     * in Magento database for future API calls and license
     * verification.
     *
     * @param $result
     */
    private function configureUserInMagento($result)
    {
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_KEY, $result['id']);
        $this->spUtility->setStoreConfig(SPConstants::API_KEY, $result['apiKey']);
        $this->spUtility->setStoreConfig(SPConstants::TOKEN, $result['token']);
        $this->spUtility->setStoreConfig(SPConstants::OTP_TYPE, '');
        $this->spUtility->setStoreConfig(SPConstants::TXT_ID, '');
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_COMPLETE_LOGIN);
        $this->getMessageManager()->addSuccessMessage(SPMessages::REG_SUCCESS);
    }


    /**
     * This function is called to handle what should happen
     * after sending of OTP fails for a phone number or email.
     *
     * @param $content
     * @throws OTPValidationFailedException
     */
    private function handleOTPValidationFailed()
    {
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_VERIFY_EMAIL);
        throw new OTPValidationFailedException;
    }
}
