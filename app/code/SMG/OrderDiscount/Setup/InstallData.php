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
            ['disc_cond_code'=>'Z333','magento_rule_type'=> 'CartRule','application_type'=>'FixedAmt'],
            ['disc_cond_code'=>'????','magento_rule_type'=> 'CartRule','application_type'=>'PercAmt'],
            ['disc_cond_code'=>'Z525','magento_rule_type'=> 'CatalogRule','application_type'=>'FixedAmt'],
            ['disc_cond_code'=>'Z526','magento_rule_type'=> 'CatalogRule','application_type'=>'PercAmt']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}
