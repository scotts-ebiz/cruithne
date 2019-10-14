<?php

namespace SMG\SPV2Theming\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\PageRepository;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Config\Model\ResourceModel\Config;

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
    private $_pageCollection;
    private $_pageFactory;
    private $_pageRepository;
    private $_resourceConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param PageCollection $pageCollection
     * @param PageFactory $pageFactory
     * @param PageRepository $pageRepository
     * @param Config $resourceConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        PageCollection $pageCollection,
        PageFactory $pageFactory,
        PageRepository $pageRepository,
        Config $resourceConfig
    ) {
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_pageCollection = $pageCollection;
        $this->_pageFactory = $pageFactory;
        $this->_pageRepository = $pageRepository;
        $this->_resourceConfig = $resourceConfig;
    }

    public function apply()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();

        $homePage = $this->_pageCollection->getItemByColumnValue('identifier', 'scotts-program-v2-home');
        if (! $homePage) {
            $homePage = $this->_pageFactory->create()->setData([
                'title' => 'Scotts Program V2 Home Page',
                'page_layout' => '1column',
                'identifier' => 'scotts-program-v2-home',
                'is_active' => 1,
            ]);
        }

        $homePage->setData('content', '{{widget type="SMG\SPV2HomePageWidget\Block\Widget\HomePage" heroHeadline="Personalized Lawn Care, Delivered to Your Door" type_name="Scotts Program Home Page"}}');
        $this->_pageRepository->save($homePage);

        // Set the new page as the default home page.
        $this->_resourceConfig->saveConfig('web/default/cms_home_page', 'scotts-program-v2-home', 'websites', 1);

        $this->_moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();

        // Remove page as the default home page.
        $this->_resourceConfig->deleteConfig('web/default/cms_home_page', 'websites', 1);

        // Delete the page.
        $homePage = $this->_pageCollection->getItemByColumnValue('identifier', 'scotts-program-v2-home');
        if ($homePage) {
            $this->_pageRepository->delete($homePage);
        }

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
