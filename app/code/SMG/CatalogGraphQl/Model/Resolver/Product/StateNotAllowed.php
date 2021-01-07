<?php

namespace SMG\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolve data
 */
class StateNotAllowed implements ResolverInterface
{
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /* @var $product Product */
        $product = $value['model'];
        $return = array();
        $attribute = $product->getData('state_not_allowed');
        $states = array();

        if ($attribute) {
            forEach (explode(',', $attribute) as $id) {
                $states[] = $product->getAttributes()['state_not_allowed']->getSource()->getOptionText($id);
            }
        }




        return $states;
    }
}
