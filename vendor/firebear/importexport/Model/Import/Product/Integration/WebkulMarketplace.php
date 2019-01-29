<?php
/**
 * WebkulMarketplace
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Integration;

use Firebear\ImportExport\Model\Import\Product;

/**
 * Class WebkulMarketplace
 * @package Firebear\ImportExport\Model\Import\Product\Integration
 */
class WebkulMarketplace extends AbstractIntegration
{
    const COL_UNASSIGN_SELLER = 'webkull_unassign_any_seller';

    /**
     *
     */
    public function importData($verbosity = false)
    {
        if ($verbosity) {
            $this->output->setVerbosity($verbosity);
        }
        $this->addLogWriteln(__('WebKul Marketplace Integration'), $this->output);
        try {
            /** @var \Webkul\Marketplace\Observer\AdminhtmlCustomerSaveAfterObserver $webKulProductManager */
            $webKulProductManager = $this->getObjectManager()
                ->get(\Webkul\Marketplace\Observer\AdminhtmlCustomerSaveAfterObserver::class);
            /** @var \Webkul\Marketplace\Helper\Data $webKulHelperManager */
            $webKulHelperManager = $this->getObjectManager()->get(\Webkul\Marketplace\Helper\Data::class);
            $webKulAssignData = [];
            $webKulUnAssignData = [];
            while ($bunch = $this->_dataSourceModel->getNextBunch()) {
                foreach ($bunch as $rowData) {
                    if (isset($rowData[Product::COL_SKU], $rowData[Product::VENDOR_ID])
                        && $webKulProductManager->isSeller($rowData[Product::VENDOR_ID])
                    ) {
                        $productIdFromSku = (int)$this->productRepository
                            ->get($rowData[Product::COL_SKU])->getId();

                        $webKulAssignData[$rowData[Product::VENDOR_ID]][] = $productIdFromSku;

                        if (isset($rowData[self::COL_UNASSIGN_SELLER])) {
                            $sellerModelId = $webKulHelperManager->getSellerProductDataByProductId($productIdFromSku)
                                ->getFirstItem()->getId();
                            if ($sellerModelId) {
                                $webKulUnAssignData[$sellerModelId][] = $productIdFromSku;
                            }
                        }
                    }
                }
            }
            foreach ($webKulUnAssignData as $sellerId => $productId) {
                $this->addLogWriteln(__('Removing Products from Seller %1', $sellerId), $this->output);
                $webKulProductManager->unassignProduct($sellerId, json_encode(array_flip($productId)));
            }
            foreach ($webKulAssignData as $sellerId => $productId) {
                $this->addLogWriteln(__('Adding Products to Seller %1', $sellerId), $this->output);
                $webKulProductManager->assignProduct($sellerId, json_encode(array_flip($productId)));
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
        }
    }
}