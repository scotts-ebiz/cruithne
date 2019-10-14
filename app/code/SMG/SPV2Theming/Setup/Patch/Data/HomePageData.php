<?php

namespace SMG\SPV2Theming\Setup\Patch\Data;

use Magento\Cms\Model\PageRepository;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Class HomePageData
 *
 * This automatically adds the home page widget to the home page body.
 *
 * @package Magento\DummyModule\Setup\Patch\Data
 */
class HomePageData implements DataPatchInterface, PatchRevertableInterface
{
    private $_moduleDataSetup;
    private $_pageRepository;
    private $_pageCollection;

    /**
     * @param  ModuleDataSetupInterface  $moduleDataSetup
     * @param  PageRepository  $pageRepository
     * @param  PageCollection  $pageCollection
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        PageCollection $pageCollection,
        PageRepository $pageRepository
    ) {
        /**
         * If before, we pass $setup as argument in install/upgrade function, from now we start
         * inject it with DI. If you want to use setup, you can inject it, with the same way as here
         */
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_pageCollection = $pageCollection;
        $this->_pageRepository = $pageRepository;
    }

    public function apply()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();

        $homePage = $this->_pageCollection->getItemByColumnValue('identifier', 'home');
        if ($homePage) {
            $homePage->setData('content', '{{widget type="SMG\SPV2HomePageWidget\Block\Widget\HomePage" heroHeadline="Personalized Lawn Care, Delivered to Your Door" type_name="Scotts Program Home Page"}}');
            $this->_pageRepository->save($homePage);
        }

        $this->_moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();
        // Nothing needs to be revert, but it will allow this change to be
        // applied again each install.
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
