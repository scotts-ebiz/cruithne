<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;

/**
 * Class Subscription
 * @package SMG\SubscriptionApi\Model
 */
class Subscription extends AbstractModel
{
    /**
     * @var SapOrderResource
     */
    protected $_resourceModel;

    /**
     * @var SubscriptionOrderCollectionFactory
     */
    protected $_subscriptionOrderCollectionFactory;

    /**
     * @var SubscriptionOrder
     */
    protected $_subscriptionOrders;

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\Subscription::class
        );
    }

//    /**
//     * Subscription constructor.
//     * @param Context $context
//     * @param \Magento\Framework\Registry $registry
//     * @param SapOrderResource $resourceModel
//     * @param SubscriptionOrderCollectionFactory $sapOrderItemCollectionFactory
//     * @param AbstractResource|null $resource
//     * @param AbstractDb|null $resourceCollection
//     * @param array $data
//     */
//    public function __construct(
//        Context $context,
//        \Magento\Framework\Registry $registry,
//        SapOrderResource $resourceModel,
//        SubscriptionOrderCollectionFactory $subscriptionoOrderCollectionFactory,
//        AbstractResource $resource = null,
//        AbstractDb $resourceCollection = null,
//        array $data = []
//    ) {
//        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
//
//        $this->_resourceModel = $resourceModel;
//        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
//
//
//    }
//
//    /**
//     * @return SubscriptionOrder
//     */
//    public function getSubscriptionOrders() {
//
//        if ( ! $this->_subscriptionOrders)
//        {
//            $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
//            $subscriptionOrders->addFieldToFilter('subscription_entity_id', $this->getEntityId());
//            $this->_subscriptionOrders = $subscriptionOrders;
//        }
//
//        return $this->_subscriptionOrders;
//    }
}