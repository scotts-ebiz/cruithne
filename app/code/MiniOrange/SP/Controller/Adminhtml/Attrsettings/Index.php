<?php

namespace MiniOrange\SP\Controller\Adminhtml\Attrsettings;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * This class handles the action for endpoint: mospsaml/attrsettings/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which 
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Index extends BaseAdminAction
{

    private $adminRoleModel;
    private $userGroupModel;

    public function __construct(\Magento\Backend\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \Magento\Framework\Message\ManagerInterface $messageManager,
                                \Psr\Log\LoggerInterface $logger,
                                \Magento\Authorization\Model\ResourceModel\Role\Collection $adminRoleModel,
                                \Magento\Customer\Model\ResourceModel\Group\Collection $userGroupModel)
    {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context,$resultPageFactory,$spUtility,$messageManager,$logger);
        $this->adminRoleModel = $adminRoleModel;
        $this->userGroupModel = $userGroupModel;
    }

    /**
     * The first function to be called when a Controller class is invoked. 
     * Usually, has all our controller logic. Returns a view/page/template 
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the 
     * database. It's called when you visis the moasaml/attrsettings/Index
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
                $this->checkIfRequiredFieldsEmpty(array('saml_am_username'=>$params,'saml_am_account_matcher'=>$params));
                $this->processValuesAndSaveData($params);
                $this->spUtility->flushCache();
                $this->messageManager->addSuccessMessage(SPMessages::SETTINGS_SAVED);
                $this->spUtility->reinitConfig();
            }
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
			$this->logger->debug($e->getMessage());
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('ATTR Settings'), __('ATTR Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
     * Process Values being submitted and save data in the database.
     * @param $param
     */
    private function processValuesAndSaveData($params)
    {
        $saml_am_default_role =  trim( $params['saml_am_default_role'] );
        $this->spUtility->setStoreConfig(SPConstants::MAP_FIRSTNAME, $params['saml_am_first_name']);
        $this->spUtility->setStoreConfig(SPConstants::MAP_LASTNAME, $params['saml_am_last_name']);
        $this->spUtility->setStoreConfig(SPConstants::MAP_DEFAULT_ROLE, $saml_am_default_role);
        $this->spUtility->setStoreConfig(SPConstants::MAP_MAP_BY, $params['saml_am_account_matcher']);

        /* ===================================================================================================
                            THE LINES OF CODE BELOW ARE PREMIUM PLUGIN SPECIFIC 
           ===================================================================================================
        */

        $saml_am_dont_allow_unlisted_user_role 
            = isset( $params['saml_am_dont_allow_unlisted_user_role'] ) ? "checked" : "unChecked";
        $mo_saml_dont_create_user_if_role_not_mapped 
            = isset($params['mo_saml_dont_create_user_if_role_not_mapped']) ? "checked" : "unchecked";
        $admin_role_mapping = $this->processAdminRoleMapping($params);
        $customer_role_mapping = $this->processCustomerRoleMapping($params);
        
        $this->spUtility->setStoreConfig(SPConstants::MAP_USERNAME, $params['saml_am_username']);
        $this->spUtility->setStoreConfig(SPConstants::MAP_EMAIL, $params['saml_am_email']);
        $this->spUtility->setStoreConfig(SPConstants::MAP_GROUP, $params['saml_am_group_name']);
        $this->spUtility->setStoreConfig(SPConstants::UNLISTED_ROLE, $saml_am_dont_allow_unlisted_user_role);
        $this->spUtility->setStoreConfig(SPConstants::CREATEIFNOTMAP, $mo_saml_dont_create_user_if_role_not_mapped);
        $this->spUtility->setStoreConfig(SPConstants::ROLES_MAPPED, serialize($customer_role_mapping));
        $this->spUtility->setStoreConfig(SPConstants::GROUPS_MAPPED, serialize($admin_role_mapping));
        
    }


     /* ===================================================================================================
                            THE FUNCTION BELOW ARE PREMIUM PLUGIN SPECIFIC 
        ===================================================================================================
    */

    /**
     * Read and process the Roles saved by the
     * admin.
     * @param $params
     * @return array
     */
    private function processAdminRoleMapping($params)
    {
        $admin_role_mapping = array();
        $roles = $this->adminRoleModel->toOptionArray();
        foreach ($roles as $role) 
        {
            $attr = 'saml_am_admin_attr_values_' . $role['value'];
            if(isset($params[$attr]))
                $admin_role_mapping[$role['value']] = $params[$attr];
        }
        return $admin_role_mapping;
    }


    /**
     * Read and process the Groups saved by the
     * admin.
     * @param $params
     * @return array
     */
    private function processCustomerRoleMapping($params)
    {
        $customer_role_mapping = array();
        $groups = $this->userGroupModel->toOptionArray();
        foreach ($groups as $group) 
        {
            $attr = 'saml_am_group_attr_values_' . $group['value'];
            if(isset($params[$attr]))
                $customer_role_mapping[$group['value']] = $params[$attr];
        }
        return $customer_role_mapping;
    }


    /**
     * Is the user allowed to view the Attribute Mapping settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_ATTR);
    }
}
