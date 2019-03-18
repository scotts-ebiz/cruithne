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
        $tableName = $setup->getTable('smg_discount_codes');

        // create the data
        $data = [
            ['magento_desc'=>'10% Off','magento_coupon_code'=>'10%OFF','disc_cond_code'=>'Z525','disc_fixed_amt'=> NULL,'disc_perc_amt'=>'10','discount_title' => '10 %', 'discount_values' => '10', 'discount_type' => 'percentage'],
            ['magento_desc'=>'25% Off','magento_coupon_code'=>'25%OFF','disc_cond_code'=>'Z525','disc_fixed_amt'=> NULL,'disc_perc_amt'=>'25','discount_title' => '25 %', 'discount_values' => '25', 'discount_type' => 'percentage'],
            ['magento_desc'=>'50% Off','magento_coupon_code'=>'50%OFF','disc_cond_code'=>'Z525','disc_fixed_amt'=> NULL,'disc_perc_amt'=>'50','discount_title' => '50 %', 'discount_values' => '50', 'discount_type' => 'percentage'],
            ['magento_desc'=>'100% Off','magento_coupon_code'=>'100%OFF','disc_cond_code'=>'ZMPA','disc_fixed_amt'=> NULL,'disc_perc_amt'=>'100','discount_title' => '100 %', 'discount_values' => '100', 'discount_type' => 'percentage'],
            ['magento_desc'=>'$5 Off','magento_coupon_code'=>'5OFF','disc_cond_code'=>'Z526','disc_fixed_amt'=>'5.00','disc_perc_amt'=> NULL,'discount_title' => '$5.00', 'discount_values' => '5', 'discount_type' => 'amount'],
            ['magento_desc'=>'$10 Off','magento_coupon_code'=>'10OFF','disc_cond_code'=>'Z526','disc_fixed_amt'=>'10.00','disc_perc_amt'=> NULL,'discount_title' => '$10.00', 'discount_values' => '10', 'discount_type' => 'amount'],
            ['magento_desc'=>'$15 Off','magento_coupon_code'=>'15OFF','disc_cond_code'=>'Z526','disc_fixed_amt'=>'15.00','disc_perc_amt'=> NULL,'discount_title' => '$15.00', 'discount_values' => '15', 'discount_type' => 'amount'],
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}
