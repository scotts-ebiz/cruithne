<?php

namespace SMG\CreditReason\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        // get the table
        $tableName = $setup->getTable('credit_reason_code');

        // create the data
        $data = [
            ['reason_code' => '003', 'short_desc' => 'Defective', 'long_desc' => 'All defectives'],
            ['reason_code' => '005', 'short_desc' => 'Damaged and Refused', 'long_desc' => 'Only to be used if the product was shipped damaged or arrived damaged and the customer is returning (RE) or field destroying the product (CR).'],
            ['reason_code' => '006', 'short_desc' => 'Billed Not Shipped', 'long_desc' => 'Only to be used when the POD obtained reflects a shortage on it.'],
            ['reason_code' => '010', 'short_desc' => 'STC/POD', 'long_desc' => 'Only to be used to code  full pallet shortages that have  been denied by our customer for repayment.  POD does not reflect shortage. '],
            ['reason_code' => '011', 'short_desc' => 'Concealed Shortage/POD', 'long_desc' => 'Only to be used to code less than full pallet shortages that have either been denied by our customer for repayment, or that we didn\'t attempt to recover in the first place because of customers\' cs policy.  POD does not reflect the shortage.'],
            ['reason_code' => '014', 'short_desc' => 'Customer Refusal', 'long_desc' => 'Should only be used when a customer refuses the order for any reason, besides the product being damaged, which would be an 005.  '],
            ['reason_code' => '016', 'short_desc' => 'Short Shipment Freight Claim Filed', 'long_desc' => 'Used to code shortage claims that will have a carrier claim filed against them.'],
            ['reason_code' => '021', 'short_desc' => 'Discounts Allowed', 'long_desc' => 'All discount related credits, including new store. '],
            ['reason_code' => '023', 'short_desc' => 'Freight', 'long_desc' => 'All freight related credits.  Incorrect initial billings, redelivery fees, etc'],
            ['reason_code' => '035', 'short_desc' => 'Infestation/ Contamination', 'long_desc' => 'Only used in extenuating circumstances when OS&D should be affected by a recall of product.  Otherwise 038 should be used. '],
            ['reason_code' => '037', 'short_desc' => 'Buyback', 'long_desc' => 'Only used as a CR when customer is removing approved buyback  items from the store without a return. If entering a return, 037 should be used to identify all buyback returns.'],
            ['reason_code' => '038', 'short_desc' => 'Recovery/Recall', 'long_desc' => 'Only to be used when crediting or returning recalled product from a customer'],
            ['reason_code' => '040', 'short_desc' => 'Order Error (Item)', 'long_desc' => 'Ordering Errors on behalf of SMG - USE WHEN CUSTOMER/BDT ORDERS INCORRECTLY'],
            ['reason_code' => '041', 'short_desc' => 'Wrong Item Shipped', 'long_desc' => 'Only to be used when the wrong sku was picked and shipped from the DC.  If the wrong sku or qty was ordered by the customer, and the warehouse shipped that sku or qty they ordered, that needs to be a 040 Order Error (Item).'],
            ['reason_code' => '098', 'short_desc' => 'Seed lot failed testing', 'long_desc' => 'All credits related to the failed seed, expired seed, or seed that cannot be overlabeled.'],
            ['reason_code' => '100', 'short_desc' => 'Price Discrepancy', 'long_desc' => 'All pricing related credits.'],
            ['reason_code' => '103', 'short_desc' => 'Quantity Discrepancy', 'long_desc' => 'Should only be used to credit under threshold shortage claims that are not researched because of their dollar value'],
            ['reason_code' => '113', 'short_desc' => 'Policy Credit', 'long_desc' => 'Should encompass all instances in which we would normally use reason codes such as 017 Display Allowance and 121 Retailer Promotion'],
            ['reason_code' => 'F01-Fll', 'short_desc' => 'Fine', 'long_desc' => 'All fine related credits'],
            ['reason_code' => '008', 'short_desc' => 'Shipped not Billed', 'long_desc' => 'Only to be used on debits issues'],
            ['reason_code' => '016', 'short_desc' => 'Carrier Claim', 'long_desc' => 'Only to be used on carrier claim issues'],
            ['reason_code' => '034', 'short_desc' => 'Dead Overage/Free Astray', 'long_desc' => 'Only to be used on debits issues']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}