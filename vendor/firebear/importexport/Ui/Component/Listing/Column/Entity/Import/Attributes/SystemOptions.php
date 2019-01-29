<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Attributes;

use Firebear\ImportExport\Helper\Data;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\Import\Customer;
use Firebear\ImportExport\Model\Import\Address;
use Firebear\ImportExport\Model\Import\CustomerComposite;
use Firebear\ImportExport\Model\Source\Import\Config;
use Magento\ImportExport\Model\Import\Entity\Factory;

/**
 * Class Options
 */
class SystemOptions implements OptionSourceInterface
{

    const CATALOG_PRODUCT = 'catalog_product';

    const CATALOG_CATEGORY = 'catalog_category';

    const ADVANCED_PRICING = 'advanced_pricing';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attributeCollection;

    /**
     * @var Product
     */
    protected $productImportModel;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var CustomerComposite
     */
    protected $composite;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Factory
     */
    protected $entityFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Options constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeFactory
     * @param Product $productImportModel
     * @param Customer $customer
     * @param Address $address
     * @param CustomerComposite $composite
     * @param Config $config
     * @param Factory $entityFactory
     * @param Data $helper
     */
    public function __construct(
        CollectionFactory $attributeFactory,
        Config $config,
        Factory $entityFactory,
        Data $helper
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->config = $config;
        $this->entityFactory = $entityFactory;
        $this->helper = $helper;
    }

    /**
     * @param int $withoutGroup
     *
     * @return array
     */
    public function toOptionArray($withoutGroup = 0)
    {

        $options = $this->getAttributeCatalog($withoutGroup);

        $this->options = $options;

        return $this->options;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    {

        $this->attributeCollection = $this->attributeFactory
            ->create()
            ->addVisibleFilter()
            ->setOrder('attribute_code', AbstractDb::SORT_ORDER_ASC);

        return $this->attributeCollection;
    }

    /**
     * @param int $withoutGroup
     *
     * @return array
     */
    protected function getAttributeCatalog($withoutGroup = 0)
    {
        $attributeCollection = $this->getAttributeCollection()
//            ->addFieldToFilter('frontend_input', ['neq' => 'select'])
            ->addFieldToFilter('attribute_code', ['nin' => ['sku', 'url_key']]);
        $subOptions = [];
        foreach ($attributeCollection as $attribute) {
            $label = (!$withoutGroup) ? $attribute->getAttributeCode() . ' (' . $attribute->getFrontendLabel() . ')' : $attribute->getAttributeCode();
            $subOptions[] =
                [
                    'label' => $label,
                    'value' => $attribute->getAttributeCode()
                ];
        }

        return $subOptions;
    }
}
