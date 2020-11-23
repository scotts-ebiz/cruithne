<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogPermissions\Model\Permission;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->setId(3)
    ->setCreatedAt('2014-06-23 09:50:07')
    ->setName('Allow category')
    ->setParentId(2)
    ->setPath('1/2/3')
    ->setLevel(2)
    ->setAvailableSortBy('name')
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->setAvailableSortBy(['position'])
    ->setIsAnchor(1)
    ->save();

$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->setId(4)
    ->setCreatedAt('2014-06-23 09:50:07')
    ->setName('Deny category')
    ->setParentId(2)
    ->setPath('1/2/4')
    ->setLevel(2)
    ->setAvailableSortBy('name')
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->setAvailableSortBy(['position'])
    ->save();

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(Product::class);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId(122)
    ->setAttributeSetId($product->getDefaultAttributeSetId())
    ->setStoreId(1)
    ->setWebsiteIds([1])
    ->setName('Allow category product')
    ->setSku('simple_allow_122')
    ->setUrlKey('simple_allow_122')
    ->setPrice(111)
    ->setWeight(18)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCategoryIds([3])
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED);

$productRepository->save($product);

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(Product::class);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId(133)
    ->setAttributeSetId($product->getDefaultAttributeSetId())
    ->setStoreId(1)
    ->setWebsiteIds([1])
    ->setName('Deny category product')
    ->setSku('simple_deny_122')
    ->setUrlKey('simple_deny_122')
    ->setPrice(111)
    ->setWeight(18)
    ->setStockData(['use_config_manage_stock' => 0])
    ->setCategoryIds([4])
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED);

$productRepository->save($product);

/** @var $permission \Magento\CatalogPermissions\Model\Permission */
$permission = $objectManager->create(Permission::class);
$permission->setEntityId(1)
    ->setWebsiteId(1)
    ->setCategoryId(3)
    ->setCustomerGroupId(0)
    ->setGrantCatalogCategoryView(Permission::PERMISSION_ALLOW)
    ->setGrantCatalogProductPrice(Permission::PERMISSION_ALLOW)
    ->setGrantCheckoutItems(Permission::PERMISSION_ALLOW)
    ->save();

/** @var $permission Permission */
$permission = $objectManager->create(Permission::class);
$permission->setEntityId(2)
    ->setWebsiteId(1)
    ->setCategoryId(4)
    ->setCustomerGroupId(0)
    ->setGrantCatalogCategoryView(Permission::PERMISSION_DENY)
    ->setGrantCatalogProductPrice(Permission::PERMISSION_DENY)
    ->setGrantCheckoutItems(Permission::PERMISSION_DENY)
    ->save();
