<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Exception\PasswordMismatchException;
use MiniOrange\SP\Helper\Exception\OTPSendingFailedException;

/**
 * Handles registration of new user account. This is called when the
 * registration form is submitted. Process the credentials and
 * information provided by the admin.
 *
 * This action class first checks if a customer exists with the email
 * address provided. If no customer exists then send OTP to the email
 * and start the validation process.
 */
class RegisterNewUserAction extends BaseAdminAction
{
    private $loginExistingUserAction;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \Psr\Log\LoggerInterface $logger,
        \MiniOrange\SP\Controller\Actions\LoginExistingUserAction $loginExistingUserAction
    ) {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context, $resultPageFactory, $spUtility, $logger);
        $this->loginExistingUserAction = $loginExistingUserAction;
    }

    
    /**
     * Execute function to execute the classes function.
     *
     * @throws \Exception
     */
    public function execute()
    {
        $this->checkIfRequiredFieldsEmpty(['email'=>$this->REQUEST,'password'=>$this->REQUEST,
                                            'confirmPassword'=>$this->REQUEST]);
        $email = $this->REQUEST['email'];
        $password = $this->REQUEST['password'];
        $confirmPassword = $this->REQUEST['confirmPassword'];
        $companyName = $this->REQUEST['companyName'];
        $firstName = $this->REQUEST['firstName'];
        $lastName = $this->REQUEST['lastName'];
        if (strcasecmp($confirmPassword, $password)!=0) {
            throw new PasswordMismatchException;
        }
        $result = $this->checkIfUserExists($email);
        if (strcasecmp($result['status'], 'CUSTOMER_NOT_FOUND') == 0) {
            $this->startVerificationProcess($result, $email, $companyName, $firstName, $lastName, $password);
        } else {
            $this->loginExistingUserAction
                 ->setRequestParam($this->REQUEST)
                 ->execute();
        }
    }


    /**
     * Function is used to make a cURL call which will check
     * if a user exists with the given credentials. If a user
     * is found then his details are fetched automatically and
     * saved.
     *
     * @param $email
     * @return mixed
     */
    private function checkIfUserExists($email)
    {
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL, $email);
        $content = Curl::check_customer($email);
        return json_decode($content, true);
    }


    /**
     * Customer doesn't exist in the miniOrange system. So the
     * user needs to be verified. This function starts the
     * verification process by sending OTP to his email.
     *
     * @param $result
     * @param $email
     * @param $companyName
     * @param $firstName
     * @param $lastName
     * @throws OTPSendingFailedException
     */
    private function startVerificationProcess($result, $email, $companyName, $firstName, $lastName, $temp)
    {
        $result = Curl::mo_send_otp_token(SPConstants::OTP_TYPE_EMAIL, $email);
        $result = json_decode($result, true);
        if (strcasecmp($result['status'], 'SUCCESS') == 0) {
            $this->handleOTPSentSuccess($result, $email, $companyName, $firstName, $lastName, $temp);
        } else {
            $this->handleOTPSendFailed();
        }
    }


    /**
     * This function is called to handle what should happen
     * after OTP has been sent successfully to the user's
     * email address. Show him the validate OTP screen.
     * Set the Transaction ID and otpType in session so
     * that it can fetched later on.
     *
     * @param $result
     * @param $email
     * @param $companyName
     * @param $firstName
     * @param $lastName
     */
    private function handleOTPSentSuccess($result, $email, $companyName, $firstName, $lastName, $pass)
    {
        // set session and database values
        $this->spUtility->setStoreConfig(SPConstants::TXT_ID, $result['txId']);
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL, $email);
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_CNAME, $companyName);
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_FIRSTNAME, $firstName);
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_LASTNAME, $lastName);
        $this->spUtility->setStoreConfig(SPConstants::OTP_TYPE, SPConstants::OTP_TYPE_EMAIL);
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_VERIFY_EMAIL);
        $this->spUtility->getAdminSession()->setTempKey($pass);
        $this->getMessageManager()->addSuccessMessage(SPMessages::parse('EMAIL_OTP_SENT', ['email'=>$email]));
    }


    /**
     * This function is called to handle what should happen
     * after sending of OTP fails for an email address.
     *
     * @param $content
     * @throws OTPSendingFailedException
     */
    private function handleOTPSendFailed()
    {
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_VERIFY_EMAIL);
        throw new OTPSendingFailedException;
    }
}
