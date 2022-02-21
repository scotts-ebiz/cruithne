<?php

namespace MiniOrange\SP\Helper;

use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\Data;
use MiniOrange\SP\Helper\Exception\InvalidOperationException;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;

/**
 * This class contains some common Utility functions
 * which can be called from anywhere in the module. This is
 * mostly used in the action classes to get any utility
 * function or data from the database.
 */
class SPUtility extends Data
{
    protected $adminSession;
    protected $customerSession;
    protected $authSession;
    protected $cacheTypeList;
    protected $cacheFrontendPool;
    protected $fileSystem;
    protected $reinitableConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\User\Model\UserFactory $adminFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Backend\Helper\Data $helperBackend,
        \Magento\Framework\Url $frontendUrl,
        \Magento\Backend\Model\Session $adminSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        \Magento\Framework\App\Config\ReinitableConfigInterface $reinitableConfig
    ) {
        $this->adminSession = $adminSession;
        $this->customerSession = $customerSession;
        $this->authSession = $authSession;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->fileSystem = $fileSystem;
        $this->reinitableConfig = $reinitableConfig;
           parent::__construct(
               $scopeConfig,
               $adminFactory,
               $customerFactory,
               $urlInterface,
               $configWriter,
               $assetRepo,
               $helperBackend,
               $frontendUrl
           );
    }

    /**
     * This function returns phone number as a obfuscated
     * string which can be used to show as a message to the user.
     *
     * @param $phone references the phone number.
     */
    public function getHiddenPhone($phone)
    {
        $hidden_phone = 'xxxxxxx' . substr($phone, strlen($phone) - 3);
        return $hidden_phone;
    }
    

    /**
     * This function checks if a value is set or
     * empty. Returns true if value is empty
     *
     * @return True or False
     * @param $value references the variable passed.
     */
    public function isBlank($value)
    {
        if (! isset($value) || empty($value)) {
            return true;
        }
        return false;
    }
    

    /**
     * This function checks if cURL has been installed
     * or enabled on the site.
     *
     * @return True or False
     */
    public function isCurlInstalled()
    {
        if (in_array('curl', get_loaded_extensions())) {
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * This function checks if the phone number is in the correct format or not.
     *
     * @param $phone refers to the phone number entered
     */
    public function validatePhoneNumber($phone)
    {
        if (!preg_match(MoIDPConstants::PATTERN_PHONE, $phone, $matches)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * This function is used to obfuscate and return
     * the email in question.
     *
     * @param $email refers to the email id to be obfuscated
     * @return obfuscated email id.
     */
    public function getHiddenEmail($email)
    {
        if (!isset($email) || trim($email)==='') {
            return "";
        }

        $emailsize = strlen($email);
        $partialemail = substr($email, 0, 1);
        $temp = strrpos($email, "@");
        $endemail = substr($email, $temp-1, $emailsize);
        for ($i=1; $i<$temp; $i++) {
            $partialemail = $partialemail . 'x';
        }

        $hiddenemail = $partialemail . $endemail;
               
        return $hiddenemail;
    }
    
    
    /**
     * set Admin Session Data
     *
     * @param $key
     * @param $value
     */
    public function setAdminSessionData($key, $value)
    {
        return $this->adminSession->setData($key, $value);
    }
    

    /**
     * get Admin Session data based of on the key
     *
     * @param $key
     * @param $remove
     */
    public function getAdminSessionData($key, $remove = false)
    {
        return $this->adminSession->getData($key, $remove);
    }


    /**
     * set customer Session Data
     *
     * @param $key
     * @param $value
     */
    public function setSessionData($key, $value)
    {
        return $this->customerSession->setData($key, $value);
    }
    

    /**
     * Get customer Session data based off on the key
     *
     * @param $key
     * @param $remove
     */
    public function getSessionData($key, $remove = false)
    {
        return $this->customerSession->getData($key, $remove);
    }


    /**
     * Set Session data for logged in user based on if he/she
     * is in the backend of frontend. Call this function only if
     * you are not sure where the user is logged in at.
     *
     * @param $key
     * @param $value
     */
    public function setSessionDataForCurrentUser($key, $value)
    {
        if ($this->customerSession->isLoggedIn()) {
            $this->setSessionData($key, $value);
        } elseif ($this->authSession->isLoggedIn()) {
            $this->setAdminSessionData($key, $value);
        }
    }


    /**
     * Check if the admin has configured the plugin with
     * the Identity Provier. Returns true or false
     */
    public function isSPConfigured()
    {
        $loginUrl = $this->getStoreConfig(SPConstants::SAML_SSO_URL);
        return $this->isBlank($loginUrl) ? false : true;
    }


    /**
     * This function is used to check if customer has completed
     * the registration process. Returns TRUE or FALSE. Checks
     * for the email and customerkey in the database are set
     * or not.
     */
    public function micr()
    {
        $email = $this->getStoreConfig(SPConstants::SAMLSP_EMAIL);
        $key = $this->getStoreConfig(SPConstants::SAMLSP_KEY);
        return !$this->isBlank($email) && !$this->isBlank($key) ? true : false;
    }


    /**
     * Check if there's an active session of the user
     * for the frontend or the backend. Returns TRUE
     * or FALSE
     */
    public function isUserLoggedIn()
    {
        return $this->customerSession->isLoggedIn()
                || $this->authSession->isLoggedIn();
    }

    /**
     * Get the Current Admin User who is logged in
     */
    public function getCurrentAdminUser()
    {
        return $this->authSession->getUser();
    }


    /**
     * Get the Current Admin User who is logged in
     */
    public function getCurrentUser()
    {
        return $this->customerSession->getCustomer();
    }


    /**
     * Get the admin login url
     */
    public function getAdminLoginUrl()
    {
        return $this->getAdminUrl('adminhtml/auth/login');
    }

    /**
     * Get the customer login url
     */
    public function getCustomerLoginUrl()
    {
        return $this->getUrl('customer/account/login');
    }

    /**
     * Desanitize the cert
     */
    public function desanitizeCert($cert)
    {
        return SAML2Utilities::desanitize_certificate($cert);
    }


    /**
     * Sanitize the cert
     */
    public function sanitizeCert($cert)
    {
        return SAML2Utilities::sanitize_certificate($cert);
    }


    /**
     * Flush Magento Cache. This has been added to make
     * sure the admin/user has a smooth experience and
     * doesn't have to flush his cache over and over again
     * to see his changes.
     */
    public function flushCache()
    {
        $types = ['db_ddl']; // we just need to clear the database cache
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
    
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }


    /**
     * Get data in the file specified by the path
     */
    public function getFileContents($file)
    {
        return $this->fileSystem->fileGetContents($file);
    }

    
    /**
     * Put data in the file specified by the path
     */
    public function putFileContents($file, $data)
    {
        $this->fileSystem->filePutContents($file, $data);
    }


    /** Get the Current User's logout url */
    public function getLogoutUrl()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->getUrl('customer/account/logout');
        }
        if ($this->authSession->isLoggedIn()) {
            return $this->getAdminUrl('adminhtml/auth/logout');
        }
        return '/';
    }


    /** Get the Magento Version */
    public static function getMagnetoVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        return $productMetadata->getVersion();
    }

    public function getAdminSession()
    {
        return $this->adminSession;
    }

    public function reinitConfig(){
        $this->reinitableConfig->reinit();
    }
}
