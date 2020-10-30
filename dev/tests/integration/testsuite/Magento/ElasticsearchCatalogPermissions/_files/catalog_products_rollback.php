<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$productSkus = ['simple_allow_122', 'simple_deny_122'];
foreach ($productSkus as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        //Product already removed
    }
}

/** @var $category \Magento\Catalog\Model\Category */
$category = $objectManager->create(\Magento\Catalog\Model\Category::class);
$category->load(3);
if ($category->getId()) {
    $category->delete();
}
$category->load(4);
if ($category->getId()) {
    $category->delete();
}

$bootstrap = \Magento\TestFramework\Helper\Bootstrap::getInstance();
$bootstrap->reinitialize();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
