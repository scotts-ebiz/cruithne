<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/20/18
 * Time: 8:22 AM
 */

namespace SMG\Sap\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;

class InstallData implements InstallDataInterface
{
    /**
     * Inserts data into the desired tables during the install of the module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        // get the table
        $tableName = $setup->getTable('sales_order_status_sap');

        // create the data
        $data = [
            ['status' => 'created', 'label' => 'Created'],
            ['status' => 'created_approved', 'label' => 'Created - Approved'],
            ['status' => 'created_blocked', 'label' => 'Created - Blocked'],
            ['status' => 'capture', 'label' => 'Capture'],
            ['status' => 'order_shipped', 'label' => 'Order Shipped'],
            ['status' => 'updated', 'label' => 'Updated']
        ];

        // insert the rows
        $setup->getConnection()->insertMultiple($tableName, $data);
    }
}