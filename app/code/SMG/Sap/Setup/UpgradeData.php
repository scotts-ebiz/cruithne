<?php

namespace SMG\Sap\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<'))
        {
            $this->addDataVersion110($setup);
        }
    }

    private function addDataVersion110(ModuleDataSetupInterface $setup)
    {
        // get the table
        $tableName = $setup->getTable('sales_order_status_sap');

        // create the data
        $data = [
            ['status' => 'updated', 'label' => 'Updated']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}