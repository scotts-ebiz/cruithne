<?php

namespace SMG\OrderDiscount\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        // get the table
        $tableName = $setup->getTable('smg_order_discount');

        // create the data
        $data = [
            ['discount_title' => '10 %', 'discount_values' => '10', 'discount_type' => 'percentage'],
            ['discount_title' => '25 %', 'discount_values' => '25', 'discount_type' => 'percentage'],
            ['discount_title' => '50 %', 'discount_values' => '50', 'discount_type' => 'percentage'],
            ['discount_title' => '100 %', 'discount_values' => '100', 'discount_type' => 'percentage'],
            ['discount_title' => '$5.00', 'discount_values' => '5', 'discount_type' => 'amount'],
            ['discount_title' => '$10.00', 'discount_values' => '10', 'discount_type' => 'amount'],
            ['discount_title' => '$15.00', 'discount_values' => '15', 'discount_type' => 'amount'],
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}
