<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Duplicate;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

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
     * @var CmsPage
     */
    protected $cmsPage;

    protected $duplicateFields = [
        'product' => ['sku', 'scope', 'url_key'],
        'customer' => [\Magento\CustomerImportExport\Model\Import\Customer::COLUMN_EMAIL],
        'address' => [],
        'composite' => [\Magento\CustomerImportExport\Model\Import\Customer::COLUMN_EMAIL],
        'cmsPage' => []
    ];

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $newOptions = [];
        foreach ($this->duplicateFields as $fields) {
            $newOptions = array_merge($newOptions, $fields);
        }

        $this->options = array_unique($newOptions);

        return $this->options;
    }
}
