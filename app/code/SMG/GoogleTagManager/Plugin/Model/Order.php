<?php

namespace SMG\GoogleTagManager\Plugin\Model;

use MagePal\GoogleTagManager\Model\Order as MagePalOrder;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Order {

    protected $_productRepository;

    public function __construct(ProductRepositoryInterface $productRepository) {
        $this->_productRepository = $productRepository;
    }

    public function afterGetOrderDataLayer(MagePalOrder $shipping, $result) {
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
