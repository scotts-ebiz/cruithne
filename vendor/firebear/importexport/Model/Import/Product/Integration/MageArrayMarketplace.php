<?php
/**
 * MageArrayMarketplace
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Integration;

use Firebear\ImportExport\Model\Import\Product;

/**
 * Class MageArrayMarketplace
 * @package Firebear\ImportExport\Model\Import\Product\Integration
 */
class MageArrayMarketplace extends AbstractIntegration
{
    const MAGE_PRICE_COMPARE = 'magearray_price_compare';

    public function importData($verbosity = false)
    {
        $this->addLogWriteln(__('MageArray Marketplace Integration'), $this->output);
        try {
            /** @var \MageArray\MaMarketPlace\Helper\Data $mageArrayHelper */
            $mageArrayHelper = $this->getObjectManager()
                ->get(\MageArray\MaMarketPlace\Helper\Data::class);
            /** @var \MageArray\MaMarketPlace\Model\Product $mageArrayProduct */
            $mageArrayProduct = $this->getObjectManager()
                ->get(\MageArray\MaMarketPlace\Model\Product::class);
            while ($bunch = $this->_dataSourceModel->getNextBunch()) {
                foreach ($bunch as $rowData) {
                    if (isset($rowData[Product::COL_SKU], $rowData[Product::VENDOR_ID])
                        && $mageArrayHelper->getVendorByUserId($rowData[Product::VENDOR_ID])->getIsActive()
                    ) {
                        $product = $this->productRepository->get($rowData[Product::COL_SKU]);
                        $productIdFromSku = (int)$product->getId();
                        $stockQty = $this->stockItem
                            ->getStockQty($productIdFromSku, $product->getStore()->getWebsiteId());
                        $vendorId = (int)$mageArrayHelper->getVendorByProductId($productIdFromSku)->getUserId();
                        if ($vendorId === 0) {
                            $this->addLogWriteln(
                                __('Assign Products to User ID %1', $rowData[Product::VENDOR_ID]),
                                $this->output
                            );
                            $mageArrayProduct->assignProduct($rowData[Product::VENDOR_ID], $productIdFromSku);
                        } elseif ($vendorId !== (int)$rowData[Product::VENDOR_ID] || $vendorId > 0) {
                            $this->addLogWriteln(
                                __('Product already assigned to user id %1 and cannot be assigned to user id %2 for product sku %3',
                                    $vendorId, $rowData[Product::VENDOR_ID], $rowData[Product::COL_SKU]),
                                $this->output
                            );
                        }

                        if (isset($rowData[self::MAGE_PRICE_COMPARE])) {
                            try {
                                $this->importPriceCompare(
                                    $rowData[self::MAGE_PRICE_COMPARE],
                                    $productIdFromSku,
                                    $stockQty,
                                    $vendorId,
                                    $mageArrayHelper
                                );
                            } catch (\Exception $e) {
                                $this->addLogWriteln($e->getMessage(), $this->output, 'error');
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
        }
    }

    /**
     * @param $priceArray
     * @param $productId
     * @param $stockQty
     * @param $vendorId
     * @param \MageArray\MaMarketPlace\Model\Product $mageArrayHelper
     */
    protected function importPriceCompare($priceArray, $productId, $stockQty, $vendorId, $mageArrayHelper): void
    {
        /** @var \MageArray\PriceComparison\Model\PricecomparisonFactory $pricecomparisonFactory */
        $pricecomparisonFactory = $this->getObjectManager()
            ->get(\MageArray\PriceComparison\Model\PricecomparisonFactory::class);
        /** @var \MageArray\PriceComparison\Model\ResourceModel\Pricecomparison\CollectionFactory $pricecomparisonCollectionFactory */
        $pricecomparisonCollectionFactory = $this->getObjectManager()
            ->get(\MageArray\PriceComparison\Model\ResourceModel\Pricecomparison\CollectionFactory::class);
        foreach (\explode('|', $priceArray) as $vendorPriceData) {
            $priceData = [];
            $userId = 0;
            foreach (\explode(',', $vendorPriceData) as $vendorData) {
                $_vendorPrice = \explode('=', $vendorData);
                $priceData[$_vendorPrice[0]] = $_vendorPrice[1];
                if ($_vendorPrice[0] == 'vendor_id') {
                    $userId = $_vendorPrice[1];
                    $priceData[$_vendorPrice[0]] = $mageArrayHelper->getVendorByUserId($userId)->getVendorId();
                }
            }
            $qty = $priceData['qty'] ?? 0;
            $qty += $stockQty;
            $collection = $pricecomparisonCollectionFactory->create();
            $collection->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('vendor_id', $priceData['vendor_id']);
            if (!$collection->getSize() && $vendorId != $userId) {
                $this->addLogWriteln(
                    __('Price Compare added for vendor %1', $vendorId),
                    $this->output,
                    'info'
                );
                $_priceCompareModel = $pricecomparisonFactory->create();
                $_priceCompareModel->setData($priceData)
                    ->setProductId($productId)
                    ->setQty($qty)
                    ->save();
            }
        }
    }
}
