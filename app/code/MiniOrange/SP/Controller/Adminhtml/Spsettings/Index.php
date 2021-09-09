<?php

namespace MiniOrange\SP\Controller\Adminhtml\Spsettings;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * This class handles the action for endpoint: mospsaml/spsettings/Index
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
     * database. It's called when you visis the moasaml/spsettings/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed. 
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        try{
            $params = $this->getRequest()->getParams(); //get params
            $this->checkIfValidPlugin(); //check if user has registered himself
            if($this->isFormOptionBeingSaved($params)) // check if form options are being saved
            {
                // check if required values have been submitted
                $this->checkIfRequiredFieldsEmpty(array('saml_identity_name'=>$params,'saml_issuer'=>$params,
                                                        'saml_login_url'=>$params,'saml_x509_certificate'=>$params));
                $this->processValuesAndSaveData($params);
                $this->spUtility->flushCache();
                $this->messageManager->addSuccessMessage(SPMessages::SETTINGS_SAVED);
            }
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
			$this->logger->debug($e->getMessage());
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('SP Settings'), __('SP Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
     * Process Values being submitted and save data in the database.
     */
    private function processValuesAndSaveData($params)
    {
        $saml_identity_name = trim( $params['saml_identity_name'] );
        $saml_login_url = trim( $params['saml_login_url'] );
        $saml_login_binding_type = $params['saml_login_binding_type'];
        $saml_logout_binding_type = $params['saml_logout_binding_type'];
        $saml_logout_url = isset($params['saml_logout_url']) ? trim( $params['saml_logout_url'] ) : '';
        $saml_issuer = trim( $params['saml_issuer'] );
        $saml_x509_certificate = SAML2Utilities::sanitize_certificate($params['saml_x509_certificate']);
        $saml_response_signed = isset($params['saml_response_signed']) ? 1 : 0;
        $saml_assertion_signed = isset($params['saml_assertion_signed']) ? 1 : 0;
        
        $this->spUtility->setStoreConfig(SPConstants::IDP_NAME, $saml_identity_name);
        $this->spUtility->setStoreConfig(SPConstants::BINDING_TYPE, $saml_login_binding_type);
        $this->spUtility->setStoreConfig(SPConstants::SAML_SSO_URL, $saml_login_url);
        $this->spUtility->setStoreConfig(SPConstants::LOGOUT_BINDING, $saml_logout_binding_type);
        $this->spUtility->setStoreConfig(SPConstants::SAML_SLO_URL, $saml_logout_url);
        $this->spUtility->setStoreConfig(SPConstants::ISSUER, $saml_issuer);
        $this->spUtility->setStoreConfig(SPConstants::X509CERT, $saml_x509_certificate);
        $this->spUtility->setStoreConfig(SPConstants::SHOW_ADMIN_LINK,TRUE);
        $this->spUtility->setStoreConfig(SPConstants::SHOW_CUSTOMER_LINK,TRUE);
        $this->spUtility->setStoreConfig(SPConstants::RESPONSE_SIGNED,$saml_response_signed);
        $this->spUtility->setStoreConfig(SPConstants::ASSERTION_SIGNED,$saml_assertion_signed);
        $this->spUtility->reinitConfig();
    }


    /**
     * Is the user allowed to view the Service Provider settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_SPSETTINGS);
    }
}