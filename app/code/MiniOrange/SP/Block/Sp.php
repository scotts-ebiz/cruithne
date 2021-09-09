<?php
namespace MiniOrange\SP\Block;

use MiniOrange\SP\Helper\SPConstants;
 
/**
 * This class is used to denote our admin block for all our
 * backend templates. This class has certain commmon 
 * functions which can be called from our admin template pages.
 */
class Sp extends \Magento\Framework\View\Element\Template
{	
    private $spUtility;
    private $adminRoleModel;
    private $userGroupModel;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \Magento\Authorization\Model\ResourceModel\Role\Collection $adminRoleModel,
                                \Magento\Customer\Model\ResourceModel\Group\Collection $userGroupModel,
                                array $data = []) 
    {
        $this->spUtility = $spUtility;
        $this->adminRoleModel = $adminRoleModel;
        $this->userGroupModel = $userGroupModel;
        parent::__construct($context, $data);
    }

	/**
	 * This function is a test function to check if the template
	 * is being loaded properly in the frontend without any issues.
	 */
    public function getHelloWorldTxt()
    {
        return 'Hello world!';
    }


    /**
     * This function retrieves the miniOrange customer Email
     * from the database. To be used on our template pages.
     */
    public function getCustomerEmail()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SAMLSP_EMAIL);
    }


    /**
     * This function retrieves the miniOrange customer key from the 
     * database. To be used on our template pages.
     */
    public function getCustomerKey()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SAMLSP_KEY);
    }


    public function getAcsUrl()
    {
        return $this->spUtility->getAcsUrl();
    }
    /**
     * This function retrieves the miniOrange API key from the database.
     * To be used on our template pages.
     */
    public function getApiKey()
    {
        return $this->spUtility->getStoreConfig(SPConstants::API_KEY);
    }


    /**
     * This function retrieves the token key from the database.
     * To be used on our template pages.
     */
    public function getToken()
    {
        return $this->spUtility->getStoreConfig(SPConstants::TOKEN);
    }


    /**
     * This function checks if the admin has signed enabled 
     * response signed in the module settings.
     */
    public function isResponseSigned()
    {
        return $this->spUtility->getStoreConfig(SPConstants::RESPONSE_SIGNED);
    }


    /**
     * This function checks if the admin has enabled signed
     * assertion in the module settings.
     */
    public function isAssertionSigned()
    {
        return $this->spUtility->getStoreConfig(SPConstants::ASSERTION_SIGNED);
    }


    /**
     * This function checks if the SP has been configured or not.
     */
    public function isSPConfigured()
    {
        return $this->spUtility->isSPConfigured();
    }


    /**
     * This function fetches the Issuer value saved by the admin for the IDP 
     */
    public function getSAMLIssuer()
    {
        return $this->spUtility->getStoreConfig(SPConstants::ISSUER);
    }


    /**
     * This function fetches the SSO URL saved by the admin for the IDP
     */
    public function getSSOUrl()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SAML_SSO_URL);
    }


    /**
     * This function fetches the Name of the IDP saved by the admin for the IDP 
     */
    public function getIdentityProviderName()
    {
        return $this->spUtility->getStoreConfig(SPConstants::IDP_NAME);
    }


    /**
     * This function fetches the SSO binding type saved by the admin for the IDP 
     */
    public function getLoginBindingType()
    {
        return $this->spUtility->getStoreConfig(SPConstants::BINDING_TYPE);
    }


    /**
     * This function gets the admin CSS URL to be appended to the 
     * admin dashboard screen.
     */
    public function getAdminCssURL()
    {
        return $this->spUtility->getAdminCssUrl('adminSettings.css');
    }


    /**
     * This function gets the admin JS URL to be appended to the
     * admin dashboard pages for plugin functionality
     */
    public function getAdminJSURL()
    {
        return $this->spUtility->getAdminJSUrl('adminSettings.js');
    }


    /**
     * This function gets the IntelTelInput JS URL to be appended
     * to admin pages to show country code dropdown on phone number
     * fields.
     */
    public function getIntlTelInputJs()
    {
        return $this->spUtility->getAdminJSUrl('intlTelInput.min.js');
    }


    /**
     * This function fetches the X509 cert saved by the admin for the IDP
     * in the plugin settings. 
     */
    public function getX509Cert()
    {
        return $this->spUtility->getStoreConfig(SPConstants::X509CERT);
    }


    /**
     * This function fetches/creates the TEST Configuration URL of the
     * Plugin.
     */
    public function getTestUrl()
    {
        return $this->getSPInitiatedUrl(SPConstants::TEST_RELAYSTATE);
    }


    /**
     * Get/Create Issuer URL of the site
     */
    public function getIssuerUrl()
    {
        return $this->spUtility->getIssuerUrl();
    }


    /**
     * Get/Create Base URL of the site
     */
    public function getBaseUrl()
    {
        return $this->spUtility->getBaseUrl();
    }


    /**
     * Create the URL for one of the SAML SP plugin 
     * sections to be shown as link on any of the 
     * template files.
     */
    public function getExtensionPageUrl($page)
    {
        return $this->spUtility->getAdminUrl('mospsaml/'.$page.'/index');
    }


    /**
     * Reads the Tab and retrieves the current active tab
     * if any.
     */
    public function getCurrentActiveTab()
    {
        $page = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => false]);
        $start = strpos($page,'mospsaml/')+9;
        $end = strpos($page,'/index');
        return substr($page,$start,$end-$start);
    }


    /**
     * Get/Create a MetadataURL for the site
     */
    public function getMetadataUrl()
    {
        return $this->spUtility->getMetadataUrl();
    }


    /**
     * Is the option to show SSO link on the Admin login page enabled 
     * by the admin. 
     */
    public function showAdminLink()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SHOW_ADMIN_LINK);
    }


    /**
     * Is the option to show SSO link on the Customer login page enabled 
     * by the admin. 
     */
    public function showCustomerLink()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SHOW_CUSTOMER_LINK);
    }


    /**
     * Create/Get the SP initiated URL for the site.
     */
    public function getSPInitiatedUrl($relayState=NULL)
    {
        return $this->spUtility->getSPInitiatedUrl($relayState);
    }


    /**
     * This fetches the setting saved by the admin which decides if the 
     * account should be mapped to username or email in Magento.
     */
    public function getAccountMatcher()
    {
        return $this->spUtility->getStoreConfig(SPConstants::MAP_MAP_BY);
    }


    /**
     * This fetches the setting saved by the admin which decides what 
     * attribute in the SAML response should be mapped to the Magento
     * user's firstName.
     */
    public function getFirstNameMapping()
    {
        $amFirstName = $this->spUtility->getStoreConfig(SPConstants::MAP_FIRSTNAME);
        return !$this->spUtility->isBlank( $amFirstName) ?  $amFirstName : '';
    }


    /**
     * This fetches the setting saved by the admin which decides what 
     * attributein the SAML resposne should be mapped to the Magento 
     * user's lastName
     */
    public function getLastNameMapping()
    {
        $amLastName = $this->spUtility->getStoreConfig(SPConstants::MAP_LASTNAME);
        return !$this->spUtility->isBlank( $amLastName) ?  $amLastName : '';
    }
    
    public function getGroupMapping()
    {
        $amGroupName = $this->spUtility->getStoreConfig(SPConstants::MAP_GROUP);
        return !$this->spUtility->isBlank( $amGroupName) ?  $amGroupName : '';
    }

    /**
     * Get all admin roles set by the admin on his site.
     */
    public function getAllRoles()
    {
        return $this->adminRoleModel->toOptionArray();
    }  


    /**
     * Get all customer groups set by the admin on his site.
     */
    public function getAllGroups()
    {
        return $this->userGroupModel->toOptionArray();
    }


    /**
     * Get the default role to be set for the user if it
     * doesn't match any of the role/group mappings
     */
    public function getDefaultRole()
    {
        $defaultRole = $this->spUtility->getStoreConfig(SPConstants::MAP_DEFAULT_ROLE);
        return !$this->spUtility->isBlank( $defaultRole) ?  $defaultRole : SPConstants::DEFAULT_ROLE;
    }


    /**
     * This fetches the registration status in the plugin.
     * Used to detect at what stage is the user at for
     * registration with miniOrange
     */
    public function getRegistrationStatus()
    {
        return $this->spUtility->getStoreConfig(SPConstants::REG_STATUS);
    }


    /**
     * Get the Current Admin user from session
     */
    public function getCurrentAdminUser()
    {
        return $this->spUtility->getCurrentAdminUser();
    }


    /**
     * Fetches/Creates the text of the button to be shown 
     * for SP inititated login from the admin / customer
     * login pages.
     */
    public function getSSOButtonText()
    {
        $buttonText = $this->spUtility->getStoreConfig(SPConstants::BUTTON_TEXT);
        $idpName = $this->spUtility->getStoreConfig(SPConstants::IDP_NAME);
        return !$this->spUtility->isBlank( $buttonText) ?  $buttonText : 'Login with ' . $idpName;
    }

    
     /** 
     * Get base url of miniorange
     */
    public function getMiniOrangeUrl()
    {
        return $this->spUtility->getMiniOrangeUrl();
    }


    /* ===================================================================================================
                THE FUNCTIONS BELOW ARE PREMIUM PLUGIN SPECIFIC AND DIFFER IN THE FREE VERSION
       ===================================================================================================
     */
    
    /**
     * This function checks if the user has completed the registration
     * and verification process. Returns TRUE or FALSE.
     */
    public function isEnabled()
    {
        return $this->spUtility->micr() 
            && $this->spUtility->mclv();
    }

    /*===========================================================================================
						THESE ARE PREMIUM PLUGIN SPECIFIC FUNCTIONS
    =============================================================================================*/

    /**
     * Get the SP Cert URL 
     */
    public function getPublicCert()
    {
        return $this->spUtility->getAdminCertResourceUrl('sp-certificate.crt');
    }

    
    /**
     * Get the SP Cert Content to be added to the metadata
     */
    public function getPublicCertContent()
    {
        $certificate = $this->spUtility->getFileContents($this->spUtility->getResourcePath('sp-certificate.crt' ));
        return $this->spUtility->desanitizeCert($certificate);
    }

    /**
     * Just check and return if the user has verified his 
     * license key to activate the plugin. Mostly used 
     * on the account page to show the verify license key
     * screen.
     */
    public function isVerified()
    {
        return $this->spUtility->mclv();
    }

     /**
     * This function fetches the SLO URL value saved by the admin for the IDP 
     */
    public function getLogoutUrl()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SAML_SLO_URL);
    }


    /**
     * This function fetches the Logout Binding value saved by the admin for the IDP 
     */
    public function getLogoutBindingType()
    {
        return $this->spUtility->getStoreConfig(SPConstants::LOGOUT_BINDING);
    }

    /**
     * Get/Create IDP guide base URL for admins to download
     */
    public function getIdpGuideBaseUrl($idp)
    {
        return $this->spUtility->getIdpGuideBaseUrl($idp);
    }
    
    /**
     * Get Admin Logout URL for the site
     */
    public function getAdminLogoutUrl()
    {
        return $this->spUtility->getLogoutUrl();
    }


    /**
     * Get Customer Login URL for the site
     */
    public function getCustomerLoginUrl()
    {
        return $this->spUtility->getCustomerLoginUrl();
    }


    /**
     * Get Admin Login URL for the site
     */
    public function getAdminLoginUrl()
    {
        return $this->spUtility->getAdminLoginUrl();
    }


    /**
     * Is Force Authn setting enabled by the Admin
     */
    public function getForceAutn()
    {
        return $this->spUtility->getStoreConfig(SPConstants::FORCE_AUTHN);
    }


    /**
     * Is auto redirect enabled by the admin
     */
    public function isAutoRedirectEnabled()
    {
        return $this->spUtility->getStoreConfig(SPConstants::AUTO_REDIRECT);
    }


    /**
     * Is Back Door bypass URL enabled by the admin
     */
    public function isByBackDoorEnabled()
    {
        return $this->spUtility->getStoreConfig(SPConstants::BACKDOOR);
    }
    
    /**
     * This fetches the setting saved by the admin which decides what 
     * attributein the SAML resposne should be mapped to the Magento 
     * user's email
     */
    public function getEmailMapping()
    {
        $amEmail = $this->spUtility->getStoreConfig(SPConstants::MAP_EMAIL);
        return !$this->spUtility->isBlank( $amEmail) ?  $amEmail : 'NameID';
    }

    /**
     * This fetches the setting saved by the admin which decides what 
     * attribute in the SAML response should be mapped to the Magento
     * Username.
     */
    public function samlUsernameMapping()
    {
        $samlAmUsername = $this->spUtility->getStoreConfig(SPConstants::MAP_USERNAME);
        return !$this->spUtility->isBlank( $samlAmUsername) ?  $samlAmUsername : 'NameID';
    }

    /**
     * This fetches the setting saved by the admin which decides what 
     * attribute in the SAML response should be mapped to the Magento 
     * user's lastName
     *
     * @return array
     */
    public function getGroupsMapping()
    {
        $amGroupName = $this->spUtility->getStoreConfig(SPConstants::MAP_GROUP);
        return !$this->spUtility->isBlank( $amGroupName) ?  unserialize($amGroupName) : array();
    }


    /**
     * This fetches the setting saved by the admin which doesn't allow
     * roles to be assigned to unlisted users. 
     */
    public function getDisallowUnlistedUserRole()
    {
        $disallowUnlistedRole = $this->spUtility->getStoreConfig(SPConstants::UNLISTED_ROLE);
        return !$this->spUtility->isBlank( $disallowUnlistedRole) ?  $disallowUnlistedRole : '';
    }


    /**
     * This fetches the setting saved by the admin which doesn't allow
     * users to be created if roles are not mapped based on the admin settings.
     */
    public function getDisallowUserCreationIfRoleNotMapped()
    {
        $disallowUserCreationIfRoleNotMapped = $this->spUtility->getStoreConfig(SPConstants::CREATEIFNOTMAP);
        return !$this->spUtility->isBlank( $disallowUserCreationIfRoleNotMapped) ?  $disallowUserCreationIfRoleNotMapped : '';
    }

    
    /**
     * This fetches the setting saved by the admin which maps the 
     * attributes in the SAML response to the Magento Admin Roles.
     *
     * @return array
     */
    public function getRolesMapped()
    {
        $rolesMapped = $this->spUtility->getStoreConfig(SPConstants::ROLES_MAPPED);


  return !$this->spUtility->isBlank( $rolesMapped) ?  unserialize($rolesMapped) : array();
    }


     /**
     * This fetches the setting saved by the admin which maps the 
     * attributes in the SAML response to the Magento Customer Groups.
     */
    public function getGroupsMapped()
    {
        $rolesMapped = $this->spUtility->getStoreConfig(SPConstants::GROUPS_MAPPED);
       

 return !$this->spUtility->isBlank( $rolesMapped) ?  $rolesMapped : array();
    }


    /**
     * Fetches if the admin has set the SAML request to 
     * do have force authentication variable set to 
     * true.
     */
    public function getForceAuthn()
    {
        return $this->spUtility->getStoreConfig(SPConstants::FORCE_AUTHN);
    }
}
