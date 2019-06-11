<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 12/18/18
 * Time: 10:44 AM
 */

namespace SMG\OfflineShipping\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->upgradeSchemaVersion110($setup);
        }

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->upgradeSchemaVersion130($setup);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    protected function upgradeSchemaVersion130(SchemaSetupInterface $setup)
    {
        $setup->startSetup();

        $tableName = $setup->getTable('shipping_condition_code');
        if (!$setup->getConnection()->tableColumnExists($tableName, 'store_id')) {
            $setup->getConnection()->addColumn(
                $tableName,
                'store_id',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'comment' => 'Related store id',
                    'length' => 5,
                    'default' => 1,
                    'unsigned' => true
                ]
            );
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    $tableName,
                    'store_id',
                    $setup->getTable('store'),
                    'store_id'
                ),
                $tableName,
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
        }

        if (!$setup->getConnection()->tableColumnExists($tableName, 'rate')) {
            $setup->getConnection()->addColumn(
                $tableName,
                'rate',
                [
                    'type' => Table::TYPE_FLOAT,
                    'nullable' => true,
                    'comment' => 'Shipping rate'
                ]
            );
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
     */
    private function upgradeSchemaVersion110(SchemaSetupInterface $setup)
    {
        // start the setup
        $setup->startSetup();

        $tableName = 'shipping_condition_code';

        // make a new table with the desired name
        $table = $setup->getConnection()->newTable($setup->getTable($tableName));

        // add the desired columns
        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'auto_increment' => true,
                'nullable' => false,
                'unsigned' => true
            ]
        );

        // add the desired columns
        $table->addColumn(
            'shipping_method',
            Table::TYPE_TEXT,
            120,
            [
                'nullable' => false
            ]
        );

        // add the desired columns
        $table->addColumn(
            'sap_shipping_method',
            Table::TYPE_TEXT,
            10,
            [
                'nullable' => false
            ]
        );

        // add the desired columns
        $table->addColumn(
            'description',
            Table::TYPE_TEXT,
            null,
            [
                'nullable' => true
            ]
        );

        // make the table
        $setup->getConnection()->createTable($table);

        // end the setup
        $setup->endSetup();
    }
}
