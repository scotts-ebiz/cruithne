<?php

namespace SMG\Gigya\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    private $_customerSetupFactory;

    public function __construct(
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->_customerSetupFactory = $customerSetupFactory;
    }

    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<=')) {
            $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->updateAttribute(Customer::ENTITY, 'firstname', 'is_required', 0);
            $customerSetup->updateAttribute(Customer::ENTITY, 'lastname', 'is_required', 0);
        }

        $setup->endSetup();
    }
}
