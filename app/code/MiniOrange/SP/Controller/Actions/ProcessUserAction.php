<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use MiniOrange\SP\Helper\Exception\MissingAttributesException;
use MiniOrange\SP\Helper\SPConstants;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * This action class processes the user attributes coming in
 * the SAML response to either log the customer or admin in
 * to their respective dashboard or create a customer or admin
 * based on the default role set by the admin and log them in
 * automatically.
 */
class ProcessUserAction extends BaseUserAction implements SerializerInterface
{
    private $attrs;
    private $relayState;
    private $sessionIndex;
    private $defaultRole;
    private $userGroupModel;
    private $adminRoleModel;
    private $adminUserModel;
    private $customerModel;
    private $customerLoginAction;
    private $customerFactory;
    private $userFactory;
    private $randomUtility;
    private $dontAllowUnlistedUserRole;
    private $dontCreateUserIfRoleNotMapped;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \Magento\Customer\Model\ResourceModel\Group\Collection $userGroupModel,
        \Magento\Authorization\Model\ResourceModel\Role\Collection $adminRoleModel,
        \Magento\User\Model\User $adminUserModel,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MiniOrange\SP\Controller\Actions\CustomerLoginAction $customerLoginAction,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Framework\Math\Random $randomUtility
    ) {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context, $spUtility);
        $this->processUserValues();
        $this->defaultRole = $spUtility->getStoreConfig(SPConstants::MAP_DEFAULT_ROLE);
        $this->dontAllowUnlistedUserRole = $spUtility->getStoreConfig(SPConstants::UNLISTED_ROLE);
        $this->dontCreateUserIfRoleNotMapped = $spUtility->getStoreConfig(SPConstants::CREATEIFNOTMAP);
        $this->userGroupModel = $userGroupModel;
        $this->adminRoleModel = $adminRoleModel;
        $this->adminUserModel = $adminUserModel;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->customerLoginAction = $customerLoginAction;
        $this->customerFactory = $customerFactory;
        $this->userFactory = $userFactory;
        $this->randomUtility = $randomUtility;
    }


    /**
     * Execute function to execute the classes function.
     *
     * @return ResponseInterface|ResultInterface|string
     * @throws MissingAttributesException
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        // throw an exception if attributes are empty
        if (empty($this->attrs)) {
            throw new MissingAttributesException;
        }
        // get and set all the necessary attributes
        $user_email = array_key_exists($this->emailAttribute, $this->attrs) ? $this->attrs[$this->emailAttribute][0] : null;
        $firstName = array_key_exists($this->firstName, $this->attrs) ? $this->attrs[$this->firstName][0] : null;
        $lastName = array_key_exists($this->lastName, $this->attrs) ? $this->attrs[$this->lastName][0]: null;
        $userName = array_key_exists($this->usernameAttribute, $this->attrs) ? $this->attrs[$this->usernameAttribute][0]: null;
        $groupName = array_key_exists($this->groupName, $this->attrs) ? $this->attrs[$this->groupName][0]: null;
        if ($this->spUtility->isBlank($this->defaultRole)) {
            $this->defaultRole = SPConstants::DEFAULT_ROLE;
        }
        if ($this->spUtility->isBlank($this->checkIfMatchBy)) {
            $this->checkIfMatchBy = SPConstants::DEFAULT_MAP_BY;
        }
        // process the user
        return $this->processUserAction(
            $user_email,
            $firstName,
            $lastName,
            $userName,
            $groupName,
            $this->defaultRole,
            $this->checkIfMatchBy,
            $this->attrs['NameID'][0]
        );
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
     * @return ResponseInterface|ResultInterface|string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processUserAction(
        $user_email,
        $firstName,
        $lastName,
        $userName,
        $groupName,
        $defaultRole,
        $checkIfMatchBy,
        $nameId
    ) {
        $admin = false;
        // check if the a customer or admin user exists based on the username or email in SAML response
        $user = $this->getAdminUserFromAttributes($checkIfMatchBy, $user_email, $userName);
        if (!$user) {
            $user = $this->getCustomerFromAttributes($user_email);
        }
        // if no user found then create user
        if (!$user) {
            $user = $this->createNewUser(
                $user_email,
                $firstName,
                $lastName,
                $userName,
                $groupName,
                $defaultRole,
                $nameId,
                $user,
                $admin
            );
        } else {
            $this->updateUserAttributes($firstName, $lastName, $groupName, $defaultRole, $nameId, $user, $admin);
        }
        // log the user in to it's respective dashboard
        error_log("here");
        error_log($this->relayState);
        return $admin ? $this->redirectToBackendAndLogin($user->getId(), $this->sessionIndex, $this->relayState)
            : $this->customerLoginAction->setUser($user)->setRelayState($this->relayState)->execute();
    }

    /**
     * This function udpates the user attributes based on the value
     * in the SAML Response. This function decides if the user is
     * a customer or an admin and update it's attribute accordingly
     *
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $groupName
     * @param $defaultRole
     * @param $user
     * @throws \Exception
     */
    private function updateUserAttributes(
        $firstName,
        $lastName,
        $groupName,
        $defaultRole,
        $nameId,
        $user,
        &$admin
    ) {
        
        $userId = $user->getId();
        $admin = is_a($user, '\Magento\User\Model\User') ? true : false;

        // update the attributes
        if (!$this->spUtility->isBlank($firstName)) {
            $this->spUtility->saveConfig(SPConstants::DB_FIRSTNAME, $firstName, $userId, $admin);
        }
        if (!$this->spUtility->isBlank($lastName)) {
            $this->spUtility->saveConfig(SPConstants::DB_LASTNAME, $lastName, $userId, $admin);
        }
        if (!$this->spUtility->isBlank($this->sessionIndex)) {
            $this->spUtility->saveConfig(SPConstants::SESSION_INDEX, $this->sessionIndex, $userId, $admin);
        }
        if (!$this->spUtility->isBlank($nameId)) {
            $this->spUtility->saveConfig(SPConstants::NAME_ID, $nameId, $userId, $admin);
        }

        $role_mapping = $admin ? $this->unserialize($this->spUtility->getStoreConfig(SPConstants::ROLES_MAPPED))
                                : $this->unserialize($this->spUtility->getStoreConfig(SPConstants::GROUPS_MAPPED));
        // process the roles
        $setRole = $this->processRoles($defaultRole, $admin, $role_mapping, $groupName);
        
        if (!empty($setRole) && !empty($this->dontAllowUnlistedUserRole)
            && $this->dontAllowUnlistedUserRole == 'checked') {
            return;
        }

        if (empty($setRole)) {
            return;
        }
        
        //update roles
        if ($admin) {
            $user->setRoleIds($setRole)->setRoleUserId($user->getUserId())->saveRelations();
        } else {
            $user->setData('group_id', $setRole[0]); // customer cannot have multiple groups
            $user->save();
        }
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
     * @return string
     */
    private function redirectToBackendAndLogin($userId, $sessionIndex, $relayState)
    {
        // set the admin query parameters to be passed on to the backend for processing
        $adminParams = ['option'=>SPConstants::LOGIN_ADMIN_OPT,'userid'=>$userId,
                            'relaystate'=>$relayState,'sessionindex'=>$sessionIndex];
        // redirect the user to the backend
        $url = $this->spUtility->getAdminUrl('adminhtml', $adminParams);
        //return $this->spUtility->getAdminUrl('adminhtml',$adminParams);
        return $this->getResponse()->setRedirect($url)->sendResponse();
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
        $siteurl = substr($siteurl, strpos($siteurl, '//'), strlen($siteurl)-1);
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
     * @return array|string|void
     * @todo : remove the n2 complexity here
     */
    private function processRoles($defaultRole, &$admin, $role_mapping, $groupName)
    {
        
        $role = [];
        $setDefaultRole = $this->processDefaultRole($admin, $defaultRole);
        if (empty($groupName) || empty($role_mapping)) {
            return $setDefaultRole;
        }
        
        foreach ($role_mapping as $role_value => $group_names) {
            $groups = explode(";", $group_names);
            foreach ($groups as $group) {
                if (in_array($group, $groupName)) {
                    array_push($role, $role_value);
                }
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
     * @return string|void
     */
    private function processDefaultRole($admin, $defaultRole)
    {
        if (is_null($defaultRole)) {
            return;
        }
        $groups = $this->userGroupModel->toOptionArray();
        $roles = $this->adminRoleModel->toOptionArray();
        $setDefaultRole = "";
        if ($admin) {
            foreach ($roles as $role) { // admin roles
                $admin = $defaultRole==$role['label'] ? true : false;
                if ($admin) {
                    $setDefaultRole = $role['value'];
                    break;
                }
            }
        } else {
            foreach ($groups as $group) { // customer roles
                $admin = $defaultRole==$group['label']? false : true;
                if (!$admin) {
                    $setDefaultRole = $group['value'];
                    break;
                }
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
     * @param $nameId
     * @param $user
     * @param $admin
     * @return \Magento\User\Model\User|null
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createNewUser(
        $user_email,
        $firstName,
        $lastName,
        $userName,
        $groupName,
        $defaultRole,
        $nameId,
        $user,
        &$admin
    ) {
        // generate random string to be inserted as a password

        
        $random_password = $this->randomUtility->getRandomString(8);
        $userName = !$this->spUtility->isBlank($userName)? $userName : $user_email;
        $email = !$this->spUtility->isBlank($user_email)? $user_email : $this->generateEmail($userName);
        $firstName = !$this->spUtility->isBlank($firstName) ? $firstName : $userName;
        $lastName = !$this->spUtility->isBlank($lastName) ? $lastName : $userName;

        $roles_mapped = $this->unserialize($this->spUtility->getStoreConfig(SPConstants::ROLES_MAPPED));
        $groups_mapped = $this->unserialize($this->spUtility->getStoreConfig(SPConstants::GROUPS_MAPPED));
        $roles_mapped = !is_array($roles_mapped) ? [] : $roles_mapped;
        $groups_mapped = !is_array($groups_mapped) ? [] : $groups_mapped;
        $role_mapping = array_merge($roles_mapped, $groups_mapped);
        
        if (!empty($this->dontCreateUserIfRoleNotMapped)
            && strcmp($this->dontCreateUserIfRoleNotMapped, 'checked') == 0 ) {
            if (!$this->isRoleMappingConfiguredForUser($role_mapping, $groupName)) {
                return null;
            }
        }

        // process the roles
        $setRole = $this->processRoles($defaultRole, $admin, $role_mapping, $groupName);
        // create admin or customer user based on the role
        $user = $admin ? $this->createAdminUser($userName, $firstName, $lastName, $email, $random_password, $setRole)
                        : $this->createCustomer($userName, $firstName, $lastName, $email, $random_password, $setRole);
        // update session index and nameID in the database for thuser
        if (!$this->spUtility->isBlank($this->sessionIndex)) {
            $this->spUtility->saveConfig(SPConstants::SESSION_INDEX, $this->sessionIndex, $user->getId(), $admin);
        }
        if (!$this->spUtility->isBlank($nameId)) {
            $this->spUtility->saveConfig(SPConstants::NAME_ID, $nameId, $user->getId(), $admin);
        }
        return $user;
    }


    /**
     * Checks if the role coming in the response matches with
     * the mapping done in the plugin. This function is only
     * called if admin has enabled the option to not create
     * users if roles are not mapped.$_COOKIE
     * @param $role_mapping
     * @param $groupName
     *
     * @return bool
     * @todo : remove the n2 complexity here
     */
    private function isRoleMappingConfiguredForUser($role_mapping, $groupName)
    {
        if (empty($groupName) || empty($role_mapping)) {
            return false;
        }
        foreach ($role_mapping as $role_value => $group_names) {
            $groups = explode(";", $group_names);
            foreach ($groups as $group) {
                if (in_array($group, $groupName)) {
                    return true;
                }
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
     * @return
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createCustomer($userName, $firstName, $lastName, $email, $random_password, $role_assigned)
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
    private function createAdminUser($userName, $firstName, $lastName, $email, $random_password, $role_assigned)
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
        $user->setRoleId($assign_role);
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
     * @throws LocalizedException
     */
    private function getAdminUserFromAttributes($checkIfMatchBy, $user_email, $userName)
    {
        /** Using the resource model to fetch admin from database based on username or email */
        $binds = ['email' => $user_email , 'username' => $userName];
        $connection = $this->adminUserModel->getResource()->getConnection(); /** Get the database connection */
        $select = $connection->select()->from($this->adminUserModel->getResource()->getMainTable())->where('email=:email OR username=:username');
        $adminUser = $connection->fetchRow($select, $binds); /** Fetch rows. Returns FALSE if no row found */
        $adminUser = is_array($adminUser) ? $this->adminUserModel->loadByUsername($adminUser['username']) : $adminUser;
        return $adminUser;
    }


    /**
     * Get the Customer User from the Attributes in the SAML response
     * Return false if the customer doesn't exist. The customer is fetched
     * by email only. There are no usernames to set for a Magento Customer.
     *
     * @param $user_email
     * @param $userName
     * @return bool|\Magento\Customer\Model\Customer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerFromAttributes($user_email)
    {
       
        $this->customerModel->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        $customer = $this->customerModel->loadByEmail($user_email);
        return !is_null($customer->getId()) ? $customer : false;
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

    /**
     * Serialize data into string
     *
     * @param string|int|float|bool|array|null $data
     * @return string|bool
     * @throws \InvalidArgumentException
     * @since 101.0.0
     */
    public function serialize($data)
    {
        // TODO: Implement serialize() method.
    }

    /**
     * Unserialize the given string
     *
     * @param string $string
     * @return string|int|float|bool|array|null
     * @throws \InvalidArgumentException
     * @since 101.0.0
     */
    public function unserialize($string)
    {
        // TODO: Implement unserialize() method.
    }
}
