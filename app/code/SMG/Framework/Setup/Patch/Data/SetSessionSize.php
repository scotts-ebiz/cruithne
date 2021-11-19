<?php
/**
 * User: cnixon
 * Date: 11/19/21
 * Time: 12:36 PM
 */

namespace SMG\Framework\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\PageRepository;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Config\Model\ResourceModel\Config;

/**
 * Class SetSessionSize
 *
 * We need large Session Sizes here at SMG.
 *
 * @package SMG\Framework\Setup\Patch\Data
 */
class SetSessionSize implements DataPatchInterface, PatchRevertableInterface
{
    private $_moduleDataSetup;
    private $_config;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Config $config
    ) {
        $this->_config = $config;
        $this->_moduleDataSetup = $moduleDataSetup;

    }

    public function apply()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();

        $this->_config->saveConfig('system/security/max_session_size_admin', '1024000', 'default', 0);
        $this->_config->saveConfig('system/security/max_session_size_storefront', '1024000', 'default', 0);

        $this->_moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();

        $this->_config->deleteConfig('system/security/max_session_size_admin');
        $this->_config->deleteConfig('system/security/max_session_size_storefront');

        $this->_moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}

