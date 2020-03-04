<?php

namespace SMG\Sales\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //Update the sales_order_grid with master_subscription_id
        if (version_compare($context->getVersion(), '1.6.0', '<')) {
            $this->addDataVersion160($setup);
        }
    }

    /**
     * Add Data for Version 1.6.0
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addDataVersion160(ModuleDataSetupInterface $setup)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $grid = $setup->getTable('sales_order_grid');
        $order = $setup->getTable('sales_order');

        $connection->query(
            $connection->updateFromSelect(
                $connection->select()
                    ->join(
                        $order,
                        sprintf('%s.entity_id = %s.entity_id', $grid, $order),
                        'master_subscription_id'
                    ),
                $grid
            )
        );
        $setup->endSetup();
    }
}