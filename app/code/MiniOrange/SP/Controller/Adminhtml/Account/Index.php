<?php

namespace MiniOrange\SP\Controller\Adminhtml\Account;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * This class handles the action for endpoint: mospsaml/account/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which 
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Index extends BaseAdminAction
{
    private $options = array (
        'registerNewUser',
        'validateNewUser',
        'resendOTP',
        'sendOTPPhone',
        'loginExistingUser',
        'resetPassword',
        'removeAccount',
        /** ====  THIS HAS BEEN ADDED FOR THE PREMIUM PLUGIN ONLY ==== **/        
        'verifyLicenseKey');

    private $registerNewUserAction;
    private $validateOTPAction;
    private $resendOTPAction;
    private $sendOTPToPhone;
    private $loginExistingUserAction;
    private $forgotPasswordAction;
    private $lkAction;

    public function __construct(\Magento\Backend\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \Magento\Framework\Message\ManagerInterface $messageManager,
                                \Psr\Log\LoggerInterface $logger,
                                \MiniOrange\SP\Controller\Actions\RegisterNewUserAction $registerNewUserAction,
                                \MiniOrange\SP\Controller\Actions\ValidateOTPAction $validateOTPAction,
                                \MiniOrange\SP\Controller\Actions\ResendOTPAction $resendOTPAction,
                                \MiniOrange\SP\Controller\Actions\SendOTPToPhone $sendOTPToPhone,
                                \MiniOrange\SP\Controller\Actions\LoginExistingUserAction $loginExistingUserAction,
                                \MiniOrange\SP\Controller\Actions\LKAction $lkAction,
                                \MiniOrange\SP\Controller\Actions\ForgotPasswordAction $forgotPasswordAction)
    {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context,$resultPageFactory,$spUtility,$messageManager,$logger);
        $this->registerNewUserAction = $registerNewUserAction;
        $this->validateOTPAction = $validateOTPAction;
        $this->resendOTPAction = $resendOTPAction;
        $this->sendOTPToPhone = $sendOTPToPhone;
        $this->loginExistingUserAction = $loginExistingUserAction;
        $this->forgotPasswordAction = $forgotPasswordAction;
        $this->lkAction = $lkAction;
    }


    /**
     * The first function to be called when a Controller class is invoked. 
     * Usually, has all our controller logic. Returns a view/page/template 
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the 
     * database. It's called when you visis the moasaml/account/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed. 
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {

        try{
            $params = $this->getRequest()->getParams();  //get params
            if($this->isFormOptionBeingSaved($params)) // check if form options are being saved
            {
                $keys 			= array_values($params);
                $operation 		= array_intersect($keys,$this->options);              
                if(count($operation) > 0) {  // route data and proccess
                    $this->_route_data(array_values($operation)[0],$params); 
                    $this->spUtility->flushCache();
                }
                $this->spUtility->reinitConfig();
            }   

        }catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
			$this->logger->debug($e->getMessage());
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('Account Settings'), __('Account Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
	 * Route the request data to appropriate functions for processing.
	 * Check for any kind of Exception that may occur during processing 
	 * of form post data. Call the appropriate action.
	 *
	 * @param $op refers to operation to perform
	 * @param $params
	 */
	private function _route_data($op,$params)
	{
		switch ($op) 
		{
			case $this->options[0]:
				$this->registerNewUserAction->setRequestParam($params)
                    ->execute();						                    break;
			case $this->options[1]:
                $this->validateOTPAction->setRequestParam($params)
                     ->execute(); 						                    break;
			case $this->options[2]:
				$this->resendOTPAction->setRequestParam($params)
                     ->execute(); 						                    break;
            case $this->options[3]:
				$this->sendOTPToPhone->setRequestParam($params)
                     ->execute(); 						                    break;
            case $this->options[4]:
				$this->loginExistingUserAction->setRequestParam($params)
                     ->execute();                                           break;
            case $this->options[5]:
				$this->forgotPasswordAction->setRequestParam($params)
                     ->execute();                                           break;
            case $this->options[6]:
				$this->lkAction->setRequestParam($params)
                     ->removeAccount(); 						            break;
            /** ====  THIS HAS BEEN ADDED FOR THE PREMIUM PLUGIN ONLY ==== **/
            case $this->options[7]:
				$this->lkAction->setRequestParam($params)
                     ->execute(); 						                    break;
		}
	}

    /**
     * Is the user allowed to view the Account settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_ACCOUNT);
    }
}