<?php

namespace SMG\CustomFields\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.2', '<'))
        {
            $this->updateColumnVersion102($setup);
        }
    }

    private function updateColumnVersion102(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        $tableName = 'sales_creditmemo_item';

        // add new column to the table
        $setup->getConnection()->addColumn(
            $tableName,
            'refunded_reason_code',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Reason Code for Refunded Item.'
            ]
        );

        // end the setup
        $setup->endSetup();
    }
}