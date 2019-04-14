<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 10:44 AM
 */

namespace SMG\OrderDiscount\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<'))
        {
            $this->updateColumnVersion110($setup);
        }

    }

    private function updateColumnVersion110(SchemaSetupInterface $setup)
    {
      
        $setup->startSetup();

        $tableName = 'sales_order';

        // make a new table with the desired table name
        $setup->getConnection()->addColumn(
            $tableName,
            'hdr_disc_fixed_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'HDR Discount Fixed Amount'
            ]
        );
        
        $setup->getConnection()->addColumn(
            $tableName,
            'hdr_disc_perc',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'HDR Discount Percentage'
            ]
        );
        
        $setup->getConnection()->addColumn(
            $tableName,
            'hdr_disc_cond_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'HDR Discount Condition Code'
            ]
        );
        
        $setup->getConnection()->addColumn(
            $tableName,
            'hdr_surch_fixed_amount',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'HDR Surch Fixed Amount'
            ]
        );
        
         $setup->getConnection()->addColumn(
            $tableName,
            'hdr_surch_perc',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'HDR Surch Percentage'
            ]
        );
        
        $setup->getConnection()->addColumn(
            $tableName,
            'hdr_surch_cond_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'HDR Surch Condition Code'
            ]
        );
        // end the setup
        $setup->endSetup();
    }
    
}
