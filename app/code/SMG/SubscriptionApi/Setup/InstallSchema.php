<?php

namespace SMG\SubscriptionApi\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

	public function install(
		SchemaSetupInterface $setup,
		ModuleContextInterface $content
	)
	{
		$installer = $setup;
		$installer->startSetup();

		$connection = $installer->getConnection();
		$table_name = $installer->getTable('smg_subscriptions');

		// Create smg_subscriptions table and add columns to it
		if( $connection->isTableExists( $table_name ) != true ) {
			$table = $connection->newTable($table_name)
				->addColumn(
					'subscription_id',
					Table::TYPE_INTEGER,
					null,
					[
						'identity'	=> true,
						'unsigned'	=> true,
						'nullable'	=> false,
						'primary'	=> true,
					]
				)
				->addColumn(
					'customer_id',
					Table::TYPE_INTEGER,
					255,
					[
						'nullable'	=> false,
					]
				)
				->addColumn(
					'quiz_id',
					Table::TYPE_TEXT,
					Table::DEFAULT_TEXT_SIZE,
					[
						'nullable'	=> false,
					]
				)
				->addColumn(
					'subscription_date',
					Table::TYPE_DATETIME,
					null,
					[
						'nullable'	=> false,
					]
				)
				->addColumn(
					'subscription_type',
					Table::TYPE_TEXT,
					Table::DEFAULT_TEXT_SIZE,
					[
						'nullable'	=> false,
					]
				)
				->addColumn(
					'subscription_status',
					Table::TYPE_TEXT,
					Table::DEFAULT_TEXT_SIZE,
					[
						'nullable'	=> false,
					]
				)
				->addColumn(
					'subscription_data',
					Table::TYPE_TEXT,
					Table::MAX_TEXT_SIZE,
					[
						'nullable'	=> false,
					]
				);

				$connection->createTable($table);
		}

		// Add columns to the sales_order_grid table
		$sales_order_grid_table = $setup->getTable('sales_order_grid');
		if( $connection->isTableExists( $sales_order_grid_table ) ) {
			$connection->addColumn(
				$installer->getTable('sales_order_grid'),
				'gigya_id',
				[
					'type'		=> Table::TYPE_TEXT,
					'length'	=> Table::DEFAULT_TEXT_SIZE,
					'nullable'	=> false,
					'comment'	=> 'Gigya ID',
				]
			);
			$connection->addColumn(
				$installer->getTable('sales_order_grid'),
				'master_subscription_id',
				[
					'type'		=> Table::TYPE_TEXT,
					'length'	=> Table::DEFAULT_TEXT_SIZE,
					'nullable'	=> false,
					'comment'	=> 'Master Subscription ID'
				]
			);
			$connection->addColumn(
				$installer->getTable('sales_order_grid'),
				'subscription_id',
				[
					'type'		=> Table::TYPE_TEXT,
					'length'	=> Table::DEFAULT_TEXT_SIZE,
					'nullable'	=> false,
					'comment'	=> 'Subscription ID'
				]
			);
			$connection->addColumn(
				$installer->getTable('sales_order_grid'),
				'subscription_type',
				[
					'type'		=> Table::TYPE_TEXT,
					'length'	=> Table::DEFAULT_TEXT_SIZE,
					'nullable'	=> false,
					'comment'	=> 'Subscription Type'
				]
			);
			$connection->addColumn(
				$installer->getTable('sales_order_grid'),
				'subscription_addon',
				[
					'type'		=> Table::TYPE_BOOLEAN,
					'nullable'	=> false,
					'comment'	=> 'Subscription Addon'
				]
			);
			$connection->addColumn(
				$installer->getTable('sales_order_grid'),
				'ship_date',
				[
					'type'		=> Table::TYPE_DATETIME,
					'nullable'	=> false,
					'comment'	=> 'Shipping Date'
				]
			);
		}

		$installer->endSetup();

	}

}