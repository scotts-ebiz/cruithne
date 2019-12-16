<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Model\Context;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrderItem\CollectionFactory as SubscriptionAddonOrderItemCollectionFactory;

/**
 * Class SubscriptionAddonOrder
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionAddonOrder extends AbstractModel
{

    /** @var SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var SubscriptionAddonOrderItemCollectionFactory */
    protected $_subscriptionAddonOrderItemCollectionFactory;

    /** @var SubscriptionOrderItemInterface */
    protected $_subscriptionAddonOrderItems;

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder::class
        );
    }

    /**
     * SubscriptionOrder constructor.
     * @param Context $context
     * @param Registry $registry
     * @param SubscriptionHelper $subscriptionHelper
     * @param SubscriptionAddonOrderItemCollectionFactory $subscriptionAddonOrderItemCollectionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        SubscriptionHelper $subscriptionHelper,
        SubscriptionAddonOrderItemCollectionFactory $subscriptionAddonOrderItemCollectionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_subscriptionAddonOrderItemCollectionFactory = $subscriptionAddonOrderItemCollectionFactory;
    }

    /**
     * Generate the shipment dates for the subscription order
     * @throws \Exception
     */
    public function generateShipDates() {
        if ( is_null($this->getShipStartDate()) || $this->getShipStartDate() == '0000-00-00 00:00:00' ) {
            $this->generateShipStartDate();
            $this->generateShipEndDate();
        }
    }

    /**
     * Get subscription addon orders
     * @param bool $selectedOnly
     * @return mixed
     */
    public function getSubscriptionAddonOrderItems( bool $selectedOnly = false )
    {

        // Make sure we have an actual subscription
        if ( is_null( $this->getEntityId() ) ) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if ( ! isset($this->_subscriptionAddonOrderItems) ) {
            $subscriptionAddonOrderItems = $this->_subscriptionAddonOrderItemCollectionFactory->create();
            $subscriptionAddonOrderItems->addFieldToFilter( 'subscription_addon_order_entity_id', $this->getEntityId() );
            if ( $selectedOnly ) {
                $subscriptionAddonOrderItems->addFieldToFilter( 'selected', 1 );
            }
            $this->_subscriptionAddonOrderItems = $subscriptionAddonOrderItems;
        }

        return $this->_subscriptionAddonOrderItems;
    }

    /**
     * Generate Ship Start Date
     * @throws \Exception
     */
    private function generateShipStartDate() {

        // Grab the shipment open window from the admin
        $shippingOpenWindow = 0;
        if ( ! empty($this->_subscriptionHelper->getShipDaysStart()) ) {
            $shippingOpenWindow = filter_var( $this->_subscriptionHelper->getShipDaysStart(), FILTER_SANITIZE_NUMBER_INT );
        }

        // Calculate the Earliest Ship Start Date
        $earliestShipStartDate = new \DateTime($this->getApplicationStartDate());
        $earliestShipStartDate->sub(new \DateInterval('P' . $shippingOpenWindow . 'D'));
        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));

        // Take either Earliest Ship Start date of Today, whichever is greater
        if ( $todayDate <= $earliestShipStartDate ) {
            $this->setShipStartDate( $earliestShipStartDate );
        } else {
            $this->setShipStartDate( $todayDate );
        }
    }

    /**
     * Generate Ship End Date
     * @throws \Exception
     */
    private function generateShipEndDate() {

        // Grab the shipment open window from the admin
        $shippingCloseWindow = 0;
        if ( ! empty($this->_subscriptionHelper->getShipDaysEnd()) ) {
            $shippingCloseWindow = filter_var( $this->_subscriptionHelper->getShipDaysEnd(), FILTER_SANITIZE_NUMBER_INT );
        }

        // Calculate the Earliest Ship Start Date
        $earliestShipEndDate = new \DateTime( $this->getApplicationEndDate() );
        $earliestShipEndDate->sub( new \DateInterval('P' . $shippingCloseWindow . 'D') );
        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));

        // Take either Earliest Ship Start date of Today, whichever is greater
        if ( $todayDate <= $earliestShipEndDate ) {
            $this->setShipEndDate( $earliestShipEndDate->format( 'Y-m-d H:i:s' ) );
        } else {
            $this->setShipEndDate( $todayDate->format( 'Y-m-d H:i:s' )  );
        }

        $this->save();
    }
}