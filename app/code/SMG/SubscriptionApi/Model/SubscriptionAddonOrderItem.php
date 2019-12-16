<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Registry;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;

/**
 * Class SubscriptionAddonOrderItem
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionAddonOrderItem extends AbstractModel
{

    /** @var ProductRepository */
    protected $_productRepository;

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
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ProductRepository $productRepository,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_productRepository = $productRepository;
    }

    /**
     * Get Catalog Product
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProduct()
    {

        // Make sure we have an actual subscription order item
        if ( is_null( $this->getEntityId() ) || is_null( $this->getCatalogProductSku() ) ) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if ( ! isset($this->_product) ) {
            $this->_product = $this->_productRepository->get( $this->getCatalogProductSku() );
        }

        return $this->_product;
    }
}