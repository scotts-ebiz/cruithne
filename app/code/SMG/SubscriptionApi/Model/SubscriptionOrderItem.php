<?php

namespace SMG\SubscriptionApi\Model;

use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;

/**
 * Class SubscriptionOrderItem
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionOrderItem extends AbstractModel
{
    /** @var ProductRepository */
    protected $_productRepository;

    /** @var SubscriptionOrder */
    protected $_subscriptionOrder;

    /** @var SubscriptionOrderCollectionFactory*/
    protected $_subscriptionOrderCollectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem::class
        );
    }

    /**
     * SubscriptionOrderItem constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ProductRepository $productRepository
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param LoggerInterface $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ProductRepository $productRepository,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        LoggerInterface $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_productRepository = $productRepository;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_logger = $logger;
    }

    /**
     * Get Catalog Product
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProduct()
    {
        // Make sure we have an actual subscription order item
        if (empty($this->getEntityId()) || empty($this->getCatalogProductSku())) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if (! isset($this->_product)) {
            try {
                $this->_product = $this->_productRepository->get($this->getCatalogProductSku());
            } catch (\Exception $ex) {
                $this->_logger->error(__($ex->getMessage() . ' SKU: ' . $this->getCatalogProductSku()));
            }
        }

        return $this->_product;
    }

    /**
     * Get the subscription ID of the order.
     *
     * @return string;
     */
    public function getSubscriptionId()
    {
        $subscriptionOrder = $this->getSubscriptionOrder();

        if (! $subscriptionOrder) {
            return '';
        }

        return $subscriptionOrder->getSubscriptionId();
    }

    /**
     * Get the subscription order ship end date.
     *
     * @return string;
     */
    public function getShipEndDate()
    {
        $subscriptionOrder = $this->getSubscriptionOrder();

        if (! $subscriptionOrder) {
            return '';
        }

        return $subscriptionOrder->getShipEndDate();
    }

    /**
     * Get the subscription order ship start date.
     *
     * @return string;
     */
    public function getShipStartDate()
    {
        $subscriptionOrder = $this->getSubscriptionOrder();

        if (! $subscriptionOrder) {
            return '';
        }

        return $subscriptionOrder->getShipStartDate();
    }

    /**
     * Get the subscription order.
     *
     * @return bool|SubscriptionOrder
     */
    public function getSubscriptionOrder()
    {
        if ($this->_subscriptionOrder) {
            return $this->_subscriptionOrder;
        }

        try {
            $this->_subscriptionOrder = $this->_subscriptionOrderCollectionFactory
                ->create()
                ->addFieldToFilter('entity_id', $this->getData('subscription_order_entity_id'))
                ->getFirstItem();

            if (! $this->_subscriptionOrder->getId()) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return $this->_subscriptionOrder;
    }
}
