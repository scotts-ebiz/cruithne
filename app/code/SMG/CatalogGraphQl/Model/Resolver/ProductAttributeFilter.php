<?php

namespace SMG\CatalogGraphQl\Model\Resolver;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\ProductFactory;

/**
 * Category filter allows to filter products collection using custom defined filters from search criteria.
 */
class ProductAttributeFilter implements CustomFilterInterface
{
    /**
     * @var configurable
     */
    protected $configurable;

    /**
     * @var collectionFactory
     */
    protected $collectionFactory;

    /**
     * @var registry
     */
    protected $registry;

    /**
     * @var ProductFactory
     */
    protected $attributeLoading;

    /**
     * @param Configurable $configurable
     * @param CollectionFactory $collectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Registry $registry
     */

    public function __construct(
        Configurable $configurable,
        CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger,
        Registry $registry,
        ProductFactory $attributeLoading
    )
    {
        $this->registry = $registry;
        $this->configurable = $configurable;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->_attributeLoading = $attributeLoading;
    }

    public function apply(Filter $filter, AbstractDb $collection)
    {
        $conditionType = $filter->getConditionType();
        $attributeName = $filter->getField();
        $attributeValue = $filter->getValue();

        if ($attributeName == 'sync_with_my_lawn_app' || $attributeName == 'mylawn_categories') {
            $conditions = [];
            foreach ($attributeValue as $label) {

                $value = getAttributeOptionId($attributeName, $label);

                $conditions[] = ['attribute' => $attributeName, 'finset' => $value];
            }
            $simpleSelect = $this->collectionFactory->create()
                ->addAttributeToFilter($conditions);

        } else {
            $simpleSelect = $this->collectionFactory->create()
                ->addAttributeToFilter($attributeName, [$conditionType => $attributeValue]);
        }

        $arr = $simpleSelect;
        $entity_ids = [];
        foreach ($arr->getData() as $a) {
            $entity_ids[] = $a['entity_id'];
        }

        $collection->getSelect()->where($collection->getConnection()->prepareSqlCondition(
            'e.entity_id', ['in' => $entity_ids]
        ));

        return true;
    }

    public function getAttributeOptionId($attribute, $label)
    {
        $poductReource = $this->_attributeLoading->create();
        $attr = $poductReource->getAttribute($attribute);
        if ($attr->usesSource()) {
            return $option_id = $attr->getSource()->getOptionId($label);
        }
    }
}