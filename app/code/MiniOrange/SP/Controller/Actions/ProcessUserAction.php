<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MiniOrange\SP\Helper\Exception\MissingAttributesException;
use MiniOrange\SP\Helper\SPConstants;
use Magento\Framework\App\Http;
use Magento\Framework\App\Http\Interceptor;

/**
 * This action class processes the user attributes coming in
 * the SAML response to either log the customer or admin in
 * to their respective dashboard or create a customer or admin
 * based on the default role set by the admin and log them in
 * automatically.
 *
 * @todo refactor and optimize this class code
 */
class ProcessUserAction extends BaseAction
{
    private $attrs;
    private $relayState;
    private $sessionIndex;
    private $emailAttribute;
    private $usernameAttribute;
    private $firstNameKey;
    private $lastNameKey;
    private $defaultRole;
    private $checkIfMatchBy;
    private $groupNameKey;
    private $userGroupModel;
    private $adminRoleModel;
    private $adminUserModel;
    private $firstName;
    private $lastName;
    private $groupName;
    private $storeManager;
    private $customerRepository;
    private $customerLoginAction;
    private $responseFactory;
    private $customerFactory;
    private $customerModel;
    private $userFactory;
    private $randomUtility;
    private $adminConfig;
    private $dontAllowUnlistedUserRole;
    private $dontCreateUserIfRoleNotMapped;
    private $_state;	
    private $_configLoader;

    public function __construct(\Magento\Backend\App\Action\Context $context,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \Magento\Customer\Model\ResourceModel\Group\Collection $userGroupModel,
                                \Magento\Authorization\Model\ResourceModel\Role\Collection $adminRoleModel,
                                \Magento\User\Model\User $adminUserModel,
                                \Magento\Customer\Model\Customer $customerModel,
                                \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
                                \Magento\Framework\App\ResponseFactory $responseFactory,
                                \MiniOrange\SP\Controller\Actions\CustomerLoginAction $customerLoginAction,
                                \Magento\Customer\Model\CustomerFactory $customerFactory,
                                \Magento\User\Model\UserFactory $userFactory,
                                \Magento\Framework\Math\Random $randomUtility,
				\Magento\Framework\App\State $_state,
				\Magento\Framework\ObjectManager\ConfigLoaderInterface $_configLoader,
				\Magento\Backend\Helper\Data $HelperBackend)
    {
        //You can use dependency injection to get any class this observer may need.
        $this->emailAttribute = $spUtility->getStoreConfig(SPConstants::MAP_EMAIL);
        $this->emailAttribute = $spUtility->isBlank($this->emailAttribute) ? SPConstants::DEFAULT_MAP_EMAIL : $this->emailAttribute;
        $this->usernameAttribute = $spUtility->getStoreConfig(SPConstants::MAP_USERNAME);
        $this->usernameAttribute = $spUtility->isBlank($this->usernameAttribute) ? SPConstants::DEFAULT_MAP_USERN : $this->usernameAttribute;
        $this->firstNameKey = $spUtility->getStoreConfig(SPConstants::MAP_FIRSTNAME);
        $this->firstNameKey = $spUtility->isBlank($this->firstNameKey) ? SPConstants::DEFAULT_MAP_FN : $this->firstNameKey;
        $this->lastNameKey = $spUtility->getStoreConfig(SPConstants::MAP_LASTNAME);
        $this->lastNameKey = $spUtility->isBlank($this->lastNameKey) ? SPConstants::MAP_LASTNAME : $this->lastNameKey;
        $this->groupNameKey = $spUtility->getStoreConfig(SPConstants::MAP_GROUP);

        $this->firstName = $spUtility->getStoreConfig(SPConstants::MAP_FIRSTNAME);
        $this->firstName = $spUtility->isBlank($this->firstName) ? SPConstants::DEFAULT_MAP_FN : $this->firstName;
        $this->lastName = $spUtility->getStoreConfig(SPConstants::MAP_LASTNAME);
        $this->defaultRole = $spUtility->getStoreConfig(SPConstants::MAP_DEFAULT_ROLE);
        $this->checkIfMatchBy = $spUtility->getStoreConfig(SPConstants::MAP_MAP_BY);
        $this->groupName = $spUtility->getStoreConfig(SPConstants::MAP_GROUP);
        $this->dontAllowUnlistedUserRole = $spUtility->getStoreConfig(SPConstants::UNLISTED_ROLE);
        $this->dontCreateUserIfRoleNotMapped = $spUtility->getStoreConfig(SPConstants::CREATEIFNOTMAP);

        $this->customerModel = $customerModel;
        $this->userGroupModel = $userGroupModel;
        $this->adminRoleModel = $adminRoleModel;
        $this->adminUserModel = $adminUserModel;
        $this->customerRepository=$customerRepository;
        $this->storeManager = $storeManager;
        $this->responseFactory = $responseFactory;
        $this->customerLoginAction = $customerLoginAction;
        $this->customerFactory = $customerFactory;
        $this->userFactory = $userFactory;
        $this->randomUtility = $randomUtility;
	$this->_state = $_state;
	$this->HelperBackend = $HelperBackend;
	$this->_configLoader = $_configLoader;	
	
        parent::__construct($context,$spUtility);
    }


    /**
     * Execute function to execute the classes function.
     *
     * @throws MissingAttributesException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        // throw an exception if attributes are empty
        if(empty($this->attrs)) throw new MissingAttributesException;
        // get and set all the necessary attributes
        
        $user_email = isset($this->attrs[$this->emailAttribute]) ? $this->attrs[$this->emailAttribute][0] : null;
        $firstName =  isset($this->attrs[$this->firstNameKey]) ? $this->attrs[$this->firstNameKey][0] : null;
        $lastName =   isset($this->attrs[$this->lastNameKey]) ? $this->attrs[$this->lastNameKey][0]: null;
        $userName =   isset($this->attrs[$this->usernameAttribute]) ? $this->attrs[$this->usernameAttribute][0]: null;
        $groupName =  isset($this->attrs[$this->groupNameKey]) ? $this->attrs[$this->groupNameKey][0]: null;

        if($this->spUtility->isBlank($this->defaultRole)) $this->defaultRole = SPConstants::DEFAULT_ROLE;
        if($this->spUtility->isBlank($this->checkIfMatchBy)) $this->checkIfMatchBy = SPConstants::DEFAULT_MAP_BY;

        // process the user
        $this->processUserAction($user_email, $firstName, $lastName, $userName, $groupName,
            $this->defaultRole, $this->checkIfMatchBy, $this->attrs['NameID'][0]);
    }


    /**
     * This function processes the user values to either create
     * a new user on the site and log him/her in or log an existing
     * user to the site. Mapping is done based on $checkIfMatchBy
     * variable. Either email or username.
     *
     * @param $user_email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $groupName
     * @param $defaultRole
     * @param $checkIfMatchBy
     * @param $nameId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function processUserAction($user_email, $firstName, $lastName, $userName,
                                       $groupName, $defaultRole, $checkIfMatchBy, $nameId)
    {
        $admin = FALSE;

	$user = $this->getAdminUserFromAttributes($user_email);
        $admin = is_a($user, '\Magento\User\Model\User') ? true : false;
       

        if(!$user) {
            $user = $this->getCustomerFromAttributes($user_email);
        }

        // if no user found then create user
        if(!$user) {

		$rolesMapped = $this->spUtility->getStoreConfig(SPConstants::ROLES_MAPPED);	
		$admin = $this->isRoleMappingConfiguredForUser (unserialize($rolesMapped), $groupName);
            $user = $this->createNewUser($user_email, $firstName, $lastName, $userName,
                $groupName, $defaultRole, $nameId, $user, $admin);
        } else {
            $user = $this->updateUserAttributes($firstName, $lastName, $groupName, $defaultRole, $nameId, $user, $admin);
        }

        // log the user in to it's respective dashboard
	if($admin) {
	
            //flow stops here
            $this->redirectToBackendAndLogin($user->getUsername(), $this->sessionIndex, $this->relayState);
        } else {
            $user = $this->customerModel->load($user->getId());
            $this->customerLoginAction->setUser($user)->setRelayState($this->relayState)->execute();
        }
    }

    /**
     * This function updates the user attributes based on the value
     * in the SAML Response. This function decides if the user is
     * a customer or an admin and update it's attribute accordingly
     *
     * @param $firstName
     * @param $lastName
     * @param $groupName
     * @param $defaultRole
     * @param $nameId
     * @param \Magento\Customer\Api\Data\CustomerInterface $user
     * @param $admin
     * @return \Magento\Customer\Api\Data\CustomerInterface|void
     * @throws \Exception
     */
    private function updateUserAttributes($firstName, $lastName, $groupName,
                                          $defaultRole, $nameId, $user, &$admin)
    {
        $userId = $user->getId();

        $admin = is_a($user,'\Magento\User\Model\User') ? TRUE : FALSE;

        // update the attributes
       if(!$this->spUtility->isBlank($firstName))
            $this->spUtility->saveConfig(SPConstants::DB_FIRSTNAME,$firstName,$userId,$admin);
        if(!$this->spUtility->isBlank($lastName))
            $this->spUtility->saveConfig(SPConstants::DB_LASTNAME,$lastName,$userId,$admin);
       
		$session_details = array("NameID"=> $nameId,"SessionIndex"=>$this->sessionIndex);
		$this->spUtility->saveConfig('extra',$session_details,$userId,$admin);
	
        $rolesMapped = $this->spUtility->getStoreConfig(SPConstants::ROLES_MAPPED);
        $groupsMapped = $this->spUtility->getStoreConfig(SPConstants::GROUPS_MAPPED);

        $role_mapping = is_array($rolesMapped) && $admin ? $rolesMapped : array();
        $role_mapping = is_array($groupsMapped) && !$admin ? array_merge($role_mapping,$groupsMapped) : $role_mapping;

        // process the roles
        $setRole = $this->processRoles($defaultRole,$admin,$role_mapping,$groupName);
        if(!empty($setRole) && !empty($this->dontAllowUnlistedUserRole)
            && $this->dontAllowUnlistedUserRole == 'checked') return;

        return $user;
    }     



    /**
     * Function redirects the user to the backend with appropriate parameters
     * in the URL which will be read in the backend portion of the code
     * and log the admin in. We can't directly log the admin in from anywhere
     * in the code as Magento doesn't allow it.
     *
     * @param $userId
     * @param $sessionIndex
     * @param $relayState
     */
    private function redirectToBackendAndLogin($userId,$sessionIndex,$relayState)
    {
	$areaCode = 'adminhtml';
    $username = $userId;

    $this->_request->setPathInfo('/admin');

		
	try{
	$this->_state->setAreaCode($areaCode);
	} catch (\Magento\Framework\Exception\LocalizedException $exception) {
      	 // do nothing
  	 }
   
	 $this->_objectManager->configure($this->_configLoader->load($areaCode));
	
	$user = $this->_objectManager->get('Magento\User\Model\User')->loadByUsername($username);

	
	$session = $this->_objectManager->get('Magento\Backend\Model\Auth\Session');
    $session->setUser($user);
    $session->processLogin();

	
    if ($session->isLoggedIn()) {
	
        $cookieManager = $this->_objectManager->get('Magento\Framework\Stdlib\CookieManagerInterface');
        $cookieValue = $session->getSessionId();
        if ($cookieValue) {
		
            $sessionConfig = $this->_objectManager->get('Magento\Backend\Model\Session\AdminConfig');
            $cookiePath = str_replace('autologin.php', 'index.php', $sessionConfig->getCookiePath());
            $cookieMetadata = $this->_objectManager->get('Magento\Framework\Stdlib\Cookie\CookieMetadataFactory')
                ->createPublicCookieMetadata()
                ->setDuration(3600)
                ->setPath($cookiePath)
                ->setDomain($sessionConfig->getCookieDomain())
                ->setSecure($sessionConfig->getCookieSecure())
                ->setHttpOnly($sessionConfig->getCookieHttpOnly());
           $cookieManager->setPublicCookie($sessionConfig->getName(), $cookieValue, $cookieMetadata);
		if (class_exists('Magento\Security\Model\AdminSessionsManager')) { 
			$adminSessionManager = $this->_objectManager->get('Magento\Security\Model\AdminSessionsManager'); 
			$adminSessionManager->processLogin(); 
		}        

	}

	//$backendUrl = $this->_objectManager->get('Magento\Backend\Model\UrlInterface');
	$backendUrl = $this->HelperBackend->getHomePageUrl();	
        //$url = str_replace('autologin.php', 'index.php', $url);
        header('Location:  '. $backendUrl);
        exit;
    }


/*
        // set the admin query parameters to be passed on to the backend for processing
        $adminParams = array('option'=>SPConstants::LOGIN_ADMIN_OPT,'userid'=>$userId,
            'relaystate'=>$relayState,'sessionindex'=>$sessionIndex);
        // redirect the user to the backend
        $this->responseFactory->create()
            ->setRedirect($this->spUtility->getAdminUrl('adminhtml',$adminParams))
            ->sendResponse();
        exit;  */
    }


    /**
     * Create a temporary email address based on the username
     * in the SAML response. Email Address is a required so we
     * need to generate a temp/fake email if no email comes from
     * the IDP in the SAML response.
     *
     * @param $userName
     * @return string
     */
    private function generateEmail($userName)
    {
        $siteurl = $this->spUtility->getBaseUrl();
        $siteurl = substr($siteurl,strpos($siteurl,'//'),strlen($siteurl)-1);
        return $userName .'@'.$siteurl;
    }


    /**
     * Process the role that needs to be assigned to the user.
     * Fetch all the roles / groups and check admin mapping to
     * select which role needs to be assigned to the user
     *
     * @param $defaultRole
     * @param $admin
     * @param $role_mapping
     * @param $groupName
     *
     * @todo : remove the n2 complexity here
     * @return array|string
     */
    private function processRoles($defaultRole,&$admin,$role_mapping,$groupName)
    {
        $role = array();
        $setDefaultRole = $this->processDefaultRole($admin,$defaultRole);
//        $setDefaultRole=1;
        if(empty($groupName) || empty($role_mapping)) return $setDefaultRole;

        foreach ($role_mapping as $role_value => $group_names)
        {
            $groups = explode(";", $group_names);
            foreach ($groups as $group)
            {
                if(in_array($group, $groupName)) array_push($role,$role_value);
            }
        }
        return empty($role) ? $setDefaultRole : $role;
    }


    /**
     * Process the default role and figure out if it's for
     * an admin or user. Return the ID of the default Role.
     *
     * @param $admin
     * @param $defaultRole
     * @return string
     */
    private function processDefaultRole($admin,$defaultRole)
    {
        if(is_null($defaultRole)) return;
        $groups = $this->userGroupModel->toOptionArray();
        $roles = $this->adminRoleModel->toOptionArray();
        $setDefaultRole = "";
        if($admin)
        {
            foreach($roles as $role)
            { // admin roles
                $admin = $defaultRole==$role['label'] ? TRUE : FALSE;
                if($admin){ $setDefaultRole = $role['value'];
                    break; }
            }
        }
        else
        {
            foreach($groups as $group)
            { // customer roles
                $admin = $defaultRole==$group['label']? FALSE : TRUE;
                if(!$admin){ $setDefaultRole = $group['value']; break; }
            }
        }
        return $setDefaultRole;
    }


    /**
     * Create a new user based on the SAML response and attributes. Log the user in
     * to it's appropriate dashboard. This class handles generating both admin and
     * customer users.
     *
     * @param $user_email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $groupName
     * @param $defaultRole
     * @param $user
     * @return \Magento\User\Model\User|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function createNewUser($user_email, $firstName, $lastName, $userName, $groupName,
                                   $defaultRole, $nameId, $user, &$admin)
    {
        // generate random string to be inserted as a password
        $random_password = $this->randomUtility->getRandomString(8);
        $userName = !$this->spUtility->isBlank($userName)? $userName : $user_email;
        $email = !$this->spUtility->isBlank($user_email)? $user_email : $this->generateEmail($userName);
        $firstName = !$this->spUtility->isBlank($firstName) ? $firstName : $userName;
        $lastName = !$this->spUtility->isBlank($lastName) ? $lastName : $userName;

        $rolesMapped = $this->spUtility->getStoreConfig(SPConstants::ROLES_MAPPED);
        $groupsMapped = $this->spUtility->getStoreConfig(SPConstants::GROUPS_MAPPED);

        $role_mapping = is_array($rolesMapped) && $admin ? $rolesMapped : array();
        $role_mapping = is_array($groupsMapped) && !$admin ? array_merge($role_mapping,$groupsMapped) : $role_mapping;

        if (strcasecmp( $this->dontCreateUserIfRoleNotMapped, 'checked') === 0 ) {
            if (!$this->isRoleMappingConfiguredForUser($role_mapping, $groupName)) return NULL;
        }

        // process the roles
        $setRole = $this->processRoles($defaultRole,$admin,$role_mapping,$groupName);
        // create admin or customer user based on the role
        $user = $admin ? $this->createAdminUser($userName,$firstName,$lastName,$email,$random_password,$setRole)
            : $this->createCustomer($userName,$firstName,$lastName,$email,$random_password,$setRole);
		$userId = $user->getId();
        // update session index and nameID in the database for the user
        $session_details = array("NameID"=> $nameId,"SessionIndex"=>$this->sessionIndex);
		$this->spUtility->saveConfig('extra',$session_details,$userId,$admin);
	
	
        return $user;
    }


    /**
     * Checks if the role coming in the response matches with
     * the mapping done in the plugin. This function is only
     * called if admin has enabled the option to not create
     * users if roles are not mapped.$_COOKIE
     * @param $role_mapping
     * @param $groupName
     * @return bool
     *
     * @todo : remove the n2 complexity here
     */
    private function isRoleMappingConfiguredForUser($role_mapping, $groupName)
    {
        if(empty($groupName) || empty($role_mapping)) return FALSE;
        foreach ($role_mapping as $role_value => $group_names)
        {
            $groups = explode(";", $group_names);
            foreach ($groups as $group)
            {
                if(in_array($group, $groupName)) return TRUE;
            }
        }
    }


    /**
     * Create a new customer.
     *
     * @param $userName
     * @param $firstName
     * @param $lastName
     * @param $email
     * @param $random_password
     * @param $role_assigned
     * @return \Magento\Customer\Model\Customer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createCustomer($userName,$firstName,$lastName,$email,$random_password,$role_assigned)
    {
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
        $customer = $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setEmail($email)
            ->setPassword($random_password)
            ->save();
        $assign_role = is_array($role_assigned) ? $role_assigned[0] : $role_assigned;
        $customer->setGroupId($assign_role); // customer cannot have multiple groups
        $customer->save();
        return $customer;
    }


    /**
     * Create a New Admin User
     *
     * @param $email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $random_password
     * @param $role_assigned
     * @return \Magento\User\Model\User
     * @throws \Exception
     */
    private function createAdminUser($userName,$firstName,$lastName,$email,$random_password,$role_assigned)
    {
        $adminInfo = [
            'username'  => $userName,
            'firstname' => $firstName,
            'lastname'  => $lastName,
            'email'     => $email,
            'password'  => $random_password,
            'interface_locale' => 'en_US',
            'is_active' => 1
        ];
        $assign_role = is_array($role_assigned) ? $role_assigned[0] : $role_assigned;
        $assign_role = empty($assign_role) ? $assign_role : 'Administrator';
        $user = $this->userFactory->create();
        $user->setData($adminInfo);
        $user->setRoleId(1);
        $user->save();
        return $user;
    }


    /**
     * Get the Admin User from the Attributes in the SAML response.
     * Return False if the admin doesn't exist. The admin is fetched
     * by email or username based on the admin settings (checkifmatchby)
     *
     * @param $checkIfMatchBy
     * @param $user_email
     * @param $userName
     * @return array|\Magento\User\Model\User
     * @throws \Magento\Framework\Exception\LocalizedException
     */
	private function getAdminUserFromAttributes($user_email)
    {
        $adminUser = false;

        $connection = $this->adminUserModel->getResource()->getConnection();
        $select = $connection->select()->from($this->adminUserModel->getResource()->getMainTable())->where('email=:email');
        $binds = ['email' => $user_email];
        $adminUser = $connection->fetchRow($select, $binds);
        $adminUser = is_array($adminUser) ? $this->adminUserModel->loadByUsername($adminUser['username']) : $adminUser;
        return $adminUser;
    }  

 
    /**
     * Get the Customer User from the Attributes in the SAML response
     * Return false if the customer doesn't exist. The customer is fetched
     * by email only. There are no usernames to set for a Magento Customer.
     *
     * @param $user_email
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomerFromAttributes($user_email)
    {
        try {
            $customer = $this->customerRepository->get($user_email, $this->storeManager->getStore()->getWebsiteId());
            return !is_null($customer) ? $customer : FALSE;
        } catch (NoSuchEntityException $e) {
            return FALSE;
        }
    }


    /** The setter function for the Attributes Parameter */
    public function setAttrs($attrs)
    {
        $this->attrs = $attrs;
        return $this;
    }


    /** The setter function for the RelayState Parameter */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
        return $this;
    }


    /** The setter function for the SessionIndex Parameter */
    public function setSessionIndex($sessionIndex)
    {
        $this->sessionIndex = $sessionIndex;
        return $this;
    }
}
