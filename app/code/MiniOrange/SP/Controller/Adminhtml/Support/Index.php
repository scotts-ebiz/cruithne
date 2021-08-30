<?php

namespace MiniOrange\SP\Controller\Adminhtml\Support;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * This class handles the action for endpoint: mospsaml/support/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which 
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 * 
 * This class handles processing and sending or support request
 */
class Index extends BaseAdminAction
{
    /**
     * The first function to be called when a Controller class is invoked. 
     * Usually, has all our controller logic. Returns a view/page/template 
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the 
     * database. It's called when you visis the moasaml/metadata/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed. 
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        try{
            $params = $this->getRequest()->getParams(); //get params
            if($this->isFormOptionBeingSaved($params))
            {
                $this->checkIfSupportQueryFieldsEmpty(array('email'=>$params,'query'=>$params));
                $email = $params['email'];
                $phone = $params['phone'];
                $query = $params['query'];
                $firstName = $params['firstName'];
                $lastName = $params['lastName'];
                $companyName = $this->spUtility->getBaseUrl();
                Curl::submit_contact_us( $email, $phone, $query, $firstName, 
                                         $lastName, $companyName );
                $this->messageManager->addSuccessMessage(SPMessages::QUERY_SENT);
            }
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
			$this->logger->debug($e->getMessage());
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('Support'), __('Support'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
     * Is the user allowed to view the Support settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_SUPPORT);
    }
}