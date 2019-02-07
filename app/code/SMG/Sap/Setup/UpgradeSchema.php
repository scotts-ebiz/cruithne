<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 10:44 AM
 */

namespace SMG\Sap\Setup;

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

        if (version_compare($context->getVersion(), '1.2.0', '<'))
        {
            $this->updateColumnVersion120($setup);
        }
    }

    private function updateColumnVersion110(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        $tableName = 'sales_order_sap';

        // make a new table with the desired table name
        $setup->getConnection()->changeColumn(
            $tableName,
            'sap_billing_doc_date',
            'sap_billing_doc_date',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => true
            ]
        );

        // end the setup
        $setup->endSetup();
    }

    private function updateColumnVersion120(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        $tableName = 'sales_order_sap';

        // make a new table with the desired table name
        $setup->getConnection()->addColumn(
            $tableName,
            'delivery_number',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'New SAP field for delivery information'
            ]
        );

        // end the setup
        $setup->endSetup();
    }
}