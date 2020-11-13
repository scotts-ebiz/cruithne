<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Wishlist\Model\ResourceModel\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;

require __DIR__ . '/../../../Magento/Customer/_files/customer.php';
require __DIR__ . '/../../../Magento/Catalog/_files/product_simple_duplicated.php';
require __DIR__ . '/../../../Magento/Catalog/_files/second_product_simple.php';

/** @var WishlistFactory $wishlistFactory */
$wishlistFactory = $objectManager->get(WishlistFactory::class);
/** @var Wishlist $wishlistResource */
$wishlistResource = $objectManager->get(Wishlist::class);

$firstWishlist = $wishlistFactory->create();
$firstWishlist->setCustomerId($customer->getId())
    ->setName('First Wish List')
    ->setVisibility(1);
$wishlistResource->save($firstWishlist);
$firstWishlist->addNewItem($product);
$firstWishlist->addNewItem($product2);

$secondWishlist = $wishlistFactory->create();
$secondWishlist->setCustomerId($customer->getId())
    ->setName('Second Wish List');
$wishlistResource->save($secondWishlist);
