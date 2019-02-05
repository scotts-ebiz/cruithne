<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model\Order\Email\Container;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order\Email\Container\Container;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\Serializer\Json as Serializer;

/**
 * Class ShipmentTeamIdentity
 * @package SMG\CustomerServiceEmail\Model\Order\Email\Container
 */
class ShipmentIdentity extends Container implements IdentityInterface
{
    /**
     * Configuration paths
     */
    const XML_PATH_EMAIL_TEAM = 'sales_email/customer_service_team/team_emails';
    const XML_PATH_EMAIL_SHIPMENT_IDENTITY = 'sales_email/customer_service_team/shipment_identity';
    const XML_PATH_EMAIL_SHIPMENT_TEMPLATE = 'sales_email/customer_service_team/shipment_template';
    const SUBSCRIBER_EMAIL = 'subscriber_email';

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Serializer $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Serializer $serializer
    ) {
        parent::__construct($scopeConfig, $storeManager);

        $this->serializer = $serializer;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Return email copy_to list
     *
     * @return array|bool
     */
    public function getEmailCopyTo()
    {
        return false;
    }

    /**
     * Return copy method
     *
     * @return mixed
     */
    public function getCopyMethod()
    {
        return '';
    }

    /**
     * Return guest template id
     *
     * @return mixed
     */
    public function getGuestTemplateId()
    {
        return '';
    }

    /**
     * Return team emails
     *
     * @return mixed
     */
    public function getServiceTeamEmails()
    {
        $serializedSubscribers = $this->getConfigValue(
            self::XML_PATH_EMAIL_TEAM, $this->getStore()->getStoreId()
        );

        if ($serializedSubscribers === null || $serializedSubscribers === '') {
            return [];
        }

        $parsedValue = $this->serializer->unserialize($serializedSubscribers);
        $unserializedValues = [];

        foreach ($parsedValue as $value) {
            $unserializedValues[] = $value[self::SUBSCRIBER_EMAIL];
        }

        return $unserializedValues;
    }

    /**
     * Return template id
     *
     * @return mixed
     */
    public function getTemplateId()
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_SHIPMENT_TEMPLATE, $this->getStore()->getStoreId());
    }

    /**
     * Return email identity
     *
     * @return mixed
     */
    public function getEmailIdentity()
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_SHIPMENT_IDENTITY, $this->getStore()->getStoreId());
    }
}
