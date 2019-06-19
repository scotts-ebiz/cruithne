<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 10:44 AM
 */

namespace SMG\ZipCodeList\Setup;

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

    /**
     * Update the widget instance table field to handle
     * large number of widget parameters
     *
     * @param SchemaSetupInterface $setup
     */
    private function updateColumnVersion110(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        $tableName = 'widget_instance';

        // make a new table with the desired table name
        $setup->getConnection()->modifyColumn(
            $tableName,
            'widget_parameters',
            [
                'type' => Table::TYPE_TEXT,
                'length' => Table::MAX_TEXT_SIZE,
                'nullable' => true,
            ]
        );

        // end the setup
        $setup->endSetup();
    }
}
