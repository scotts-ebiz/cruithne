<?php

namespace SMG\SubscriptionAccounts\Plugin\Gigya\GigyaIM\Model;

use Gigya\GigyaIM\Api\GigyaAccountServiceInterface;
use Gigya\GigyaIM\Helper\CmsStarterKit\sdk\GSApiException;
use Gigya\GigyaIM\Helper\CmsStarterKit\user\GigyaProfile;
use Gigya\GigyaIM\Helper\CmsStarterKit\user\GigyaSubscriptionContainer;
use Gigya\GigyaIM\Helper\CmsStarterKit\user\GigyaUser;
use Gigya\GigyaIM\Helper\GigyaMageHelper;
use Gigya\GigyaIM\Logger\Logger as GigyaLogger;
use Magento\Framework\Event\ManagerInterface as EventManager;

/**
 * Temporary class override to allow Customer data to be saved since Gigya Plugin doesn't support PHP 7.2.
 * This override can be removed once Gigya updates their plugin to support PHP 7.2 under the assumption that further,
 * permanent overrides are not done on this class.
 */
class GigyaAccountService implements GigyaAccountServiceInterface
{
    /**
     * Event dispatched when the Gigya data have correctly been sent to the Gigya remote service.
     */
    const EVENT_UPDATE_GIGYA_SUCCESS = 'gigya_success_sync_to_gigya';

    /**
     * Event dispatched when the Gigya data could not be sent to the Gigya remote service or when this service replies with an error (validation or other functionnal error)
     */
    const EVENT_UPDATE_GIGYA_FAILURE = 'gigya_failed_sync_to_gigya';

    /** @var  GigyaMageHelper */
    protected $gigyaMageHelper;

    /** @var EventManager */
    protected $eventManager;

    /** @var  GigyaLogger */
    protected $logger;

    /** @var array All Gigya profile attributes */
    private static $gigyaProfileAttributes = null;

    /** @var array Gigya profile attributes that shall not be updated (Gigya service validation rule) */
    private static $gigyaProfileForbiddenAttributes = [ // CATODO : should be in config.xml
        // Dynamic fields
        'likes',
        'favorites',
        'skills',
        'education',
        'phones',
        'works',
        'publications'
    ];

    /**
     * Stores the latest GigyaUser instances get from the Gigya service.
     * Used for rollback needs.
     *
     * @var array of GigyaUser
     */
    private static $loadedGigyaUsers = [];

    /**
     * Stores the latest GigyaUser instances get from the Gigya service, and that have been updated to the Gigya service.
     * Used for rollback needs.
     *
     * @var array of GigyaUser
     */
    private static $loadedAndUpdatedOriginalGigyaUsers = [];

    /**
     * GigyaAccountService constructor.
     *
     * @param GigyaMageHelper $gigyaMageHelper
     * @param EventManager $eventManager
     * @param GigyaLogger $logger
     */
    public function __construct(
        GigyaMageHelper $gigyaMageHelper,
        EventManager $eventManager,
        GigyaLogger $logger
    ) {
        $this->gigyaMageHelper = $gigyaMageHelper;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    public static function __init()
    {
        if (is_null(self::$gigyaProfileAttributes)) {
            self::$gigyaProfileAttributes = [];

            $gigyaProfileMethods = get_class_methods(GigyaProfile::class);
            if (!empty($gigyaProfileMethod)) {
                foreach ($gigyaProfileMethods as $gigyaProfileMethod) {
                    if (strpos($gigyaProfileMethod, 'get') === 0) {
                        self::$gigyaProfileAttributes[] = lcfirst(substr($gigyaProfileMethod, 3));
                    }
                }
            }
        }
    }

    /**
     * Facility to build the profile data correctly formatted for the service call.
     *
     * @param GigyaUser $gigyaAccount
     * @return array
     */
    public static function getGigyaApiProfileData(GigyaUser $gigyaAccount)
    {
        $profile = $gigyaAccount->getProfile();

        $result = [];

        foreach (self::$gigyaProfileAttributes as $gigyaProfileAttribute) {
            if (!in_array($gigyaProfileAttribute, self::$gigyaProfileForbiddenAttributes)) {
                $value = call_user_func([$profile, 'get' . $gigyaProfileAttribute]);
                if (!is_null($value)) {
                    $result[$gigyaProfileAttribute] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Facility to build the subscriptions data correctly formatted for the service call.
     *
     * @param GigyaUser $gigyaAccount
     * @return array
     */
    public static function getGigyaApiSubscriptionsData(GigyaUser $gigyaAccount)
    {
        $subscriptions = $gigyaAccount->getSubscriptions();

        $result = [];

        if ($subscriptions != null && count($subscriptions)) {
            /** @var GigyaSubscriptionContainer $subscriptionContainer */
            foreach ($subscriptions as $subscriptionId => $subscriptionContainer) {
                $subscriptionData = $subscriptionContainer->getSubscriptionAsArray();

                // Remove null value
                $subscriptionData = array_filter(
                    $subscriptionData,
                    function ($value, $key) {
                        return $value !== null;
                    },
                    ARRAY_FILTER_USE_BOTH
                );

                $result[$subscriptionId]['email'] = $subscriptionData;
            }
        }

        return $result;
    }

    /**
     * Builds the whole data correctly formatted for the service call.
     *
     * @see https://developers.gigya.com/display/GD/accounts.setAccountInfo+REST
     *
     * @param GigyaUser $gigyaAccount
     * @return array With entries uid, profile, data
     */
    public static function getGigyaApiAccountData(GigyaUser $gigyaAccount)
    {
        $rawAccountData = [
            'UID' => $gigyaAccount->getUID(),
            'regToken' => $gigyaAccount->getRegToken(),
            'addLoginEmails' => $gigyaAccount->getAddLoginEmails(),
            'conflictHandling' => $gigyaAccount->getConflictHandling(),
            'data' => $gigyaAccount->getData(),
            'isActive' => $gigyaAccount->getIsActive(),
            'isVerified' => $gigyaAccount->getIsVerified(),
            'muteWebhooks' => $gigyaAccount->getMuteWebhooks(),
            'newPassword' => $gigyaAccount->getNewPassword(),
            'password' => $gigyaAccount->getPassword(),
            'profile' => self::getGigyaApiProfileData($gigyaAccount),
            'removeLoginEmails' => $gigyaAccount->getRemoveLoginEmails(),
            'requirePasswordChange' => $gigyaAccount->getRequirePasswordChange(),
            'secretAnswer' => $gigyaAccount->getSecretAnswer(),
            'secretQuestion' => $gigyaAccount->getSecretQuestion(),
            'securityOverride' => $gigyaAccount->getSecurityOverride(),
            'subscriptions' => self::getGigyaApiSubscriptionsData($gigyaAccount),
            'preferences' => $gigyaAccount->getPreferences(),
            'rba' => $gigyaAccount->getRba(),
            'username' => $gigyaAccount->getUsername(),
            'created' => $gigyaAccount->getCreated(),
            'regSource' => $gigyaAccount->getRegSource(),
            'format' => $gigyaAccount->getFormat(),
            'callback' => $gigyaAccount->getCallback(),
            'httpStatusCodes' => $gigyaAccount->getHttpStatusCode()
        ];

        $accountData = array_filter(
            $rawAccountData,
            function ($value, $key) {
                return $value !== null;
            },
            ARRAY_FILTER_USE_BOTH
        );

        return $accountData;
    }

    /**
     * @inheritdoc
     *
     * @param bool $dispatchEvent If true (default value) will dispatch
     *                            self::EVENT_UPDATE_GIGYA_SUCCESS
     *                            or self::EVENT_UPDATE_GIGYA_FAILURE
     *
     * @throws \Gigya\GigyaIM\Helper\CmsStarterKit\sdk\GSException
     */
    public function update($gigyaAccount, $dispatchEvent = true)
    {
        $result = null;
        $gigyaApiData = self::getGigyaApiAccountData($gigyaAccount);

        try {
            /** @var string $uid */
            $uid = $gigyaApiData['UID'];

            // 1. Get current Gigya account data : they would be used if we have to perform a rollback
            // Those data could already have been successfully loaded in self::get, that's why we check their existence.
            if (!array_key_exists($uid, self::$loadedGigyaUsers)) {
                self::$loadedGigyaUsers[$uid] = $this->gigyaMageHelper->getGigyaAccountDataFromUid($uid);
            }
            $result = self::$loadedGigyaUsers[$uid];

            // 2. Update the Gigya account
            $this->gigyaMageHelper->updateGigyaAccount(
                $uid,
                $gigyaApiData
            );

            // 3. If we reach this line that means the Gigya account has been successfully updated.
            // We store the previous data (got in 1.) if a rollback is needed (rollback would occur if Magento Customer save fails)
            self::$loadedAndUpdatedOriginalGigyaUsers[$uid] = self::$loadedGigyaUsers[$uid];
            unset(self::$loadedGigyaUsers[$uid]);

            $this->logger->debug(
                'Successful call to Gigya service api',
                $gigyaApiData
            );

            if ($dispatchEvent) {
                $this->eventManager->dispatch(
                    self::EVENT_UPDATE_GIGYA_SUCCESS,
                    [
                        'customer_entity_id' => $gigyaAccount->getCustomerEntityId()
                    ]
                );
            }
        } catch (GSApiException $e) {
            $message = $e->getLongMessage();
            $this->logger->error(
                'Failure encountered on call to Gigya service API',
                [
                    'customer_entity_id' => $gigyaAccount->getCustomerEntityId(),
                    'gigya_data' => $gigyaApiData,
                    'exception' => [
                        'code' => $e->getCode(),
                        'message' => $message
                    ]
                ]
            );

            if ($dispatchEvent) {
                $this->eventManager->dispatch(
                    self::EVENT_UPDATE_GIGYA_FAILURE,
                    [
                        'customer_entity_id' => $gigyaAccount->getCustomerEntityId(),
                        'customer_entity_email' => $gigyaAccount->getCustomerEntityEmail(),
                        'gigya_data' => $gigyaApiData,
                        'message' => $message
                    ]
                );
            }

            throw $e;
        }

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @param string $uid
     *
     * @return GigyaUser|mixed
     *
     * @throws GSApiException
     * @throws \Gigya\GigyaIM\Helper\CmsStarterKit\sdk\GSException
     */
    public function get($uid)
    {
        unset(self::$loadedGigyaUsers[$uid]);

        $gigyaAccountData = $this->gigyaMageHelper->getGigyaAccountDataFromUid($uid);
        // For creating a new instance : the returned instance must not point to the instance that will be stored for an eventual rollback.
        $result = unserialize(serialize($gigyaAccountData));

        self::$loadedGigyaUsers[$uid] = $gigyaAccountData;

        return $result;
    }

    /**
     * @param string $uid
     *
     * @return GigyaUser|mixed|null
     * @throws \Gigya\GigyaIM\Helper\CmsStarterKit\sdk\GSException
     */
    public function rollback($uid)
    {
        $result = null;
        $gigyaUser = (array_key_exists($uid, self::$loadedAndUpdatedOriginalGigyaUsers)) ? self::$loadedAndUpdatedOriginalGigyaUsers[$uid] : null;
        if ($gigyaUser != null) {
            try {
                $result = $this->update($gigyaUser, false);
            } catch (GSApiException $e) {
                $this->logger->warning('Could not rollback Gigya data');
            }
        }

        return $result;
    }
}

GigyaAccountService::__init();
