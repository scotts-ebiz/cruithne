<?php

namespace SMG\SubscriptionApi\Model;

use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;

/**
 * Class SubscriptionAddonOrderItem
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionAddonOrderItem extends AbstractModel
{
    /** @var ProductRepository */
    protected $_productRepository;

    /** @var SubscriptionAddonOrder */
    protected $_subscriptionAddonOrder;

    /** @var SubscriptionAddonOrderCollectionFactory */
    protected $_subscriptionAddonOrderCollectionFactory;

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
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrderItem::class
        );
    }

    /**
     * SubscriptionAddonOrderItem constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ProductRepository $productRepository
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @param LoggerInterface $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ProductRepository $productRepository,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory,
        LoggerInterface $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_productRepository = $productRepository;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;
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
        $subscriptionAddOnOrder = $this->getSubscriptionAddonOrder();

        if (! $subscriptionAddOnOrder) {
            return '';
        }

        return $subscriptionAddOnOrder->getSubscriptionId();
    }

    /**
     * Get the subscription order ship end date.
     *
     * @return string;
     */
    public function getShipEndDate()
    {
        $subscriptionAddOnOrder = $this->getSubscriptionAddonOrder();

        if (! $subscriptionAddOnOrder) {
            return '';
        }

        return $subscriptionAddOnOrder->getShipEndDate();
    }

    /**
     * Get the subscription order ship start date.
     *
     * @return string;
     */
    public function getShipStartDate()
    {
        $subscriptionAddOnOrder = $this->getSubscriptionAddonOrder();

        if (! $subscriptionAddOnOrder) {
            return '';
        }

        return $subscriptionAddOnOrder->getShipStartDate();
    }

    /**
     * Get the subscription order.
     *
     * @return bool|SubscriptionAddonOrder
     */
    public function getSubscriptionAddonOrder()
    {
        if ($this->_subscriptionAddonOrder) {
            return $this->_subscriptionAddonOrder;
        }

        try {
            $this->_subscriptionAddonOrder = $this->_subscriptionAddonOrderCollectionFactory->create()->getItemById($this->getSubscriptionAddonOrderEntityId());
        } catch (\Exception $e) {
            return false;
        }

        return $this->_subscriptionAddonOrder;
    }
}
