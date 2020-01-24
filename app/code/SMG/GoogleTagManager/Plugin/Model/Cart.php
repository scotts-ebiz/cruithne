<?php

namespace SMG\GoogleTagManager\Plugin\Model;

use MagePal\GoogleTagManager\Model\Cart as MagePalCart;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Cart {

    protected $_productRepository;

    public function __construct(ProductRepositoryInterface $productRepository) {
        $this->_productRepository = $productRepository;
    }

    public function afterGetCart(MagePalCart $shipping, $result) {
        if (!empty($result['items'])) {
            for ($i = 0; $i < count($result['items']); $i++) {
                $product = $this->_productRepository->get($result['items'][$i]['sku']);

                if ($drupalProductId = $product->getData('drupalproductid')) {
                    $result['items'][$i]['drupalproductid'] = $drupalProductId;
                }
            }
        }

        return  $result;
    }
}
