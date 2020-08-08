<?php

namespace SMG\OfflineShipping\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<'))
        {
            $this->upgradeDataVersion110($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<'))
        {
            $this->upgradeDataVersion120($setup);
        }

        if (version_compare($context->getVersion(), '1.4.0', '<'))
        {
            $this->upgradeDataVersion140($setup);
        }

        if (version_compare($context->getVersion(), '1.5.0', '<'))
        {
            $this->upgradeDataVersion150($setup);
        }
    }

    private function upgradeDataVersion110(ModuleDataSetupInterface $setup)
    {
        // get the table
        $tableName = $setup->getTable('shipping_condition_code');

        // create the data
        $data = [
            ['shipping_method' => 'flatrate_fedex-nextday', 'sap_shipping_method' => 'C4', 'description' => 'FedEx Next Day - Standard'],
            ['shipping_method' => 'flatrate_fedex-2ndday', 'sap_shipping_method' => 'C5', 'description' => 'FedEx 2nd Day'],
            ['shipping_method' => 'freeshipping_freeshipping', 'sap_shipping_method' => 'C6', 'description' => 'FedEx 3rd Day - Free Shipping']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    private function upgradeDataVersion120(ModuleDataSetupInterface $setup)
    {
        // get the table
        $tableName = $setup->getTable('shipping_condition_code');

        // create the data
        $data = [
            ['shipping_method' => 'flatrate_flat-rate-shipping', 'sap_shipping_method' => 'C6', 'description' => 'Flat Rate Shipping']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }

    private function upgradeDataVersion140(ModuleDataSetupInterface $setup)
    {
        // get the table
        $tableName = $setup->getTable('shipping_condition_code');

        // update the flatrate
        $setup->getConnection()->update($tableName, ['sap_shipping_method' => 'C4'], ['shipping_method = ?' => 'flatrate_fedex-nextday']);
        $setup->getConnection()->update($tableName, ['sap_shipping_method' => 'C6'], ['shipping_method = ?' => 'flatrate_fedex-2ndday']);
        $setup->getConnection()->update($tableName, ['sap_shipping_method' => 'C5'], ['shipping_method = ?' => 'freeshipping_freeshipping']);
        $setup->getConnection()->update($tableName, ['sap_shipping_method' => 'C5'], ['shipping_method = ?' => 'flatrate_flat-rate-shipping']);
    }

    private function upgradeDataVersion150(ModuleDataSetupInterface $setup)
    {
        // get the table
        $tableName = $setup->getTable('shipping_condition_code');

        // create the data
        $data = [
            ['shipping_method' => 'flatrate_customer-pickup', 'sap_shipping_method' => 'BB', 'description' => 'Customer Pick-up']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}
