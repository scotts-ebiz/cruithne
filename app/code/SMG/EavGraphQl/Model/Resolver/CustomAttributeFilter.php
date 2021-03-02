<?php
/**
 * User: cnixon
 * Date: 11/2/20
 * Time: 9:39 AM
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
 * Resolve data for custom attribute filter requests
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
        /** @var array $Filtertype */
        $filterType = isset($args['filter_type']) ? $args['filter_type'] : '';
        /** @var array $attributeInputs */
        $inputAttributes = $args['attributes'];
        /** @var Collection $collection */
        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $filterApplied = false;
        $attr_merge = [];
        // Cycle through attributes
        foreach ($inputAttributes as $inputAttribute) {
            $ids = [];
            $attr = [];
            $options = $this->_productAttributeRepository->get($inputAttribute['attribute_code'])->getOptions();
            // The attributes' options, but we need to find the id from the given label
            if ($options) {
                foreach ($options as $option) {
                    foreach ($inputAttribute['options'] as $inputOption) {
                        if (strtolower($option->getLabel()) === strtolower($inputOption['attribute_option_code'])) {
                            $ids[] = $option->getValue();
                        }
                    }
                }
            } else { // If there aren't options use plain text
                foreach ($inputAttribute['options'] as $inputOption) {
                    if ($inputOption['attribute_option_code']) {
                        $ids[] = $inputOption['attribute_option_code'];
                    }
                }
            }

            // Apply filters
            if($filterType == 'matches-any')
            {
                foreach ($ids as $id) {
                    $attr[] = array('attribute' => $inputAttribute['attribute_code'] ,['finset' => array($id)]);
                }

               $attr_merge = array_merge($attr_merge,$attr);

            }else{

                foreach ($ids as $id) {
                $collection->addAttributeToFilter($inputAttribute['attribute_code'], ['finset' => array($id)]);
                $filterApplied = true;
                }
            }

        }

        if($filterType == 'matches-any')
        {
          $collection->addAttributeToFilter($attr_merge);
          $filterApplied = true;
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

