<?php
/**
 * Copyright © 2018 Firebear Studio GmbH. All rights reserved.
 */

namespace Firebear\ImportExport\Plugin\Model\Import\Product\Type\Bundle;

class RelationsDataSaver
{
    protected $managment;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Relation $management
    ) {
        $this->management = $management;
    }

    public function aroundSaveSelections(
        \Magento\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver $model,
        \Closure $work,
        array $selections
    ) {
        $work($selections);
        if (!empty($selections)) {
            foreach ($selections as $item) {
                if ($item['parent_product_id'] && $item['product_id']) {
                    $this->management->addRelation($item['parent_product_id'], $item['product_id']);
                }
            }
        }
    }
}