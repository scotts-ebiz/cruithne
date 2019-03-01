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
            ['MagentoDescription'=>'10% Off','MagentoCouponCode'=>'10%OFF','DiscCondCode'=>'Z525','DiscFixedAmt'=> NULL,'SAPDiscPercAmt'=>'10','discount_title' => '10 %', 'discount_values' => '10', 'discount_type' => 'percentage'],
            ['MagentoDescription'=>'25% Off','MagentoCouponCode'=>'25%OFF','DiscCondCode'=>'Z525','DiscFixedAmt'=> NULL,'SAPDiscPercAmt'=>'25','discount_title' => '25 %', 'discount_values' => '25', 'discount_type' => 'percentage'],
            ['MagentoDescription'=>'50% Off','MagentoCouponCode'=>'50%OFF','DiscCondCode'=>'Z525','DiscFixedAmt'=> NULL,'SAPDiscPercAmt'=>'50','discount_title' => '50 %', 'discount_values' => '50', 'discount_type' => 'percentage'],
            ['MagentoDescription'=>'100% Off','MagentoCouponCode'=>'100%OFF','DiscCondCode'=>'ZMPA','DiscFixedAmt'=> NULL,'SAPDiscPercAmt'=>'100','discount_title' => '100 %', 'discount_values' => '100', 'discount_type' => 'percentage'],
            ['MagentoDescription'=>'$5 Off','MagentoCouponCode'=>'5OFF','DiscCondCode'=>'Z526','DiscFixedAmt'=>'5.00','SAPDiscPercAmt'=> NULL,'discount_title' => '$5.00', 'discount_values' => '5', 'discount_type' => 'amount'],
            ['MagentoDescription'=>'$10 Off','MagentoCouponCode'=>'10OFF','DiscCondCode'=>'Z526','DiscFixedAmt'=>'10.00','SAPDiscPercAmt'=> NULL,'discount_title' => '$10.00', 'discount_values' => '10', 'discount_type' => 'amount'],
            ['MagentoDescription'=>'$15 Off','MagentoCouponCode'=>'15OFF','DiscCondCode'=>'Z526','DiscFixedAmt'=>'15.00','SAPDiscPercAmt'=> NULL,'discount_title' => '$15.00', 'discount_values' => '15', 'discount_type' => 'amount'],
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}
