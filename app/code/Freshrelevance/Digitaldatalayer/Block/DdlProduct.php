<?php

namespace Freshrelevance\Digitaldatalayer\Block;

use \Magento\Catalog\Block\Product\AbstractProduct;

class DdlProduct extends AbstractProduct
{
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProductsCollection()
    {
        $block=$this->getLayout()->getBlock('category.products.list');
        return $block->getLoadedProductCollection();
    }
}
