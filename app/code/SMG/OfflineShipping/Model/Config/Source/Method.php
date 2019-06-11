<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\OfflineShipping\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use \SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode\CollectionFactory as ShippingConditionCollectionFactory;

/**
 * Class Method
 * @package SMG\OfflineShipping\Model\Config\Source
 */
class Method implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\ObjectManager\ContextInterface
     */
    protected $context;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ShippingConditionCollectionFactory
     */
    protected $shippingConditionCollectionFactory;

    /**
     * Method constructor.
     *
     * @param ShippingConditionCollectionFactory                $collectionFactory
     * @param StoreManagerInterface                             $storeManager
     * @param \Magento\Framework\ObjectManager\ContextInterface $context
     */
    public function __construct(
        ShippingConditionCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->shippingConditionCollectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        /** @var \SMG\OfflineShipping\Model\ShippingConditionCode $method */
        foreach ($this->getAvailableMethods($this->context->getRequest()->getParam('store')) as $method) {
            $options[] = ['value' => $method->getShippingMethod(), 'label' => $method->getDescription()];
        }
        return $options;
    }

    /**
     * @param null|int|string $storeId
     *
     * @return array
     */
    public function getAvailableMethods($storeId = null)
    {
        if ($storeId === null) {
            try {
                $storeId = $this->storeManager->getStore()->getId();
            } catch (NoSuchEntityException $e) {
                return [];
            }
        }
        /** @var \SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode\Collection $collection */
        $collection = $this->shippingConditionCollectionFactory->create();
        $collection->addFieldToFilter('store_id', $storeId);
        return $collection->getItems();
    }
}
