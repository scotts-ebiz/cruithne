<?php

namespace MiniOrange\SP\Controller\Adminhtml\Signinsettings;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * This class handles the action for endpoint: mospsaml/signinsettings/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which 
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Index extends BaseAdminAction
{
    /**
     * The first function to be called when a Controller class is invoked. 
     * Usually, has all our controller logic. Returns a view/page/template 
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the 
     * database. It's called when you visis the moasaml/signinsettings/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed. 
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams(); //get params
            $this->checkIfValidPlugin(); //check if user has registered himself 
            // check if form options are being saved
            if($this->isFormOptionBeingSaved($params)) 
            {          
                $this->processValuesAndSaveData($params);
                $this->spUtility->flushCache();
                $this->messageManager->addSuccessMessage(SPMessages::SETTINGS_SAVED);
            }
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
			$this->logger->debug($e->getMessage());
        }
        // generate page 
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('Sign In Settings'), __('Sign In Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
     * Process Values being submitted and save data in the database.
     */
    private function processValuesAndSaveData($params)
    {
        $mo_saml_show_customer_link = isset($params['mo_saml_show_customer_link']) ? 1 : 0;
        $mo_saml_show_admin_link = isset($params['mo_saml_show_admin_link']) ? 1 : 0;
        $this->spUtility->setStoreConfig(SPConstants::SHOW_CUSTOMER_LINK, $mo_saml_show_customer_link);
        $this->spUtility->setStoreConfig(SPConstants::SHOW_ADMIN_LINK, $mo_saml_show_admin_link);
        
        /* ===================================================================================================
                            THE LINES OF CODE BELOW ARE PREMIUM PLUGIN SPECIFIC 
           ===================================================================================================
        */

        $mo_saml_force_authentication = isset($params['mo_saml_force_authentication']) ? 1 : 0;
        $mo_saml_enable_login_redirect = isset($params['mo_saml_enable_login_redirect']) ? 1 : 0;
        $mo_saml_bypass_redirect = isset($params['mo_saml_bypass_redirect'] ) ? 1 : 0;
        $mo_saml_bypass_redirect = !$mo_saml_enable_login_redirect ? 0 : 1;
        $this->spUtility->setStoreConfig(SPConstants::AUTO_REDIRECT, $mo_saml_enable_login_redirect);
        $this->spUtility->setStoreConfig(SPConstants::BACKDOOR, $mo_saml_bypass_redirect);
        $this->spUtility->setStoreConfig(SPConstants::FORCE_AUTHN, $mo_saml_force_authentication);
        $this->spUtility->reinitConfig();
    }


    /**
     * Is the user allowed to view the Sign in Settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_SIGNIN);
    }
}