<?php
/**
 * User: cnixon
 * Date: 11/2/20
 * Time: 9:39 AM
 */

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace SMG\EavGraphQl\Model\Resolver;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Resolve data for custom attribute metadata requests
 */
class CustomAttributeFilter implements ResolverInterface
{

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    protected $_productAttributeRepository;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     */
    public function __construct(CollectionFactory $collectionFactory, ProductAttributeRepositoryInterface $productAttributeRepository)
    {
        $this->_collectionFactory = $collectionFactory;
        $this->_productAttributeRepository = $productAttributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {

        $products['items'] = null;
        /** @var string $attributeInput */
        $inputAttributes = $args['attributes'];
        /** @var Collection $collection */
        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $filterApplied = false;
        // Cycle through attributes
        foreach ($inputAttributes as $inputAttribute) {
            $ids = [];
            $options = $this->_productAttributeRepository->get($inputAttribute['attribute_code'])->getOptions();
            // The attributes' options, but we need to find the id from the given label
            foreach ($options as $option) {
                foreach ($inputAttribute['options'] as $inputOption) {
                    if($option->getLabel() === $inputOption['attribute_option_code']) {
                        $ids[] = $option->getValue();
                    }
                }
            }
            // Apply filters
            if ($ids) {
                foreach($ids as $id) {
                    $collection->addFieldToFilter($inputAttribute['attribute_code'], ['eq' => $id ]);
                    $filterApplied = true;
                }
            }
        }

        $items = [];
        // This was needed for it to work
        if ($filterApplied) {
            foreach ($collection->getItems() as $item) {
                $it = $item->getData();
                $it['model'] = $item; // Needed for other resolvers
                $items[] = $it;
            }
        }

        return ['items' => $items];
    }

}

