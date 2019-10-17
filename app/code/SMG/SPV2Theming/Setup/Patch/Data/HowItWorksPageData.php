<?php

namespace SMG\SPV2Theming\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\PageRepository;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class HowItWorksPageData implements DataPatchInterface, PatchRevertableInterface
{
    private $_moduleDataSetup;
    private $_pageCollection;
    private $_pageFactory;
    private $_pageRepository;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        PageCollection $pageCollection,
        PageFactory $pageFactory,
        PageRepository $pageRepository
    ) {
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_pageCollection = $pageCollection;
        $this->_pageFactory = $pageFactory;
        $this->_pageRepository = $pageRepository;
    }

    public function apply()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();

        // Make sure How It Works page does not already exist.
        $howItWorksPage = $this->_pageCollection->getItemByColumnValue('identifier', 'how-it-works');
        if ($howItWorksPage) {
            return;
        }

        // Page does not exist, so create it.
        $howItWorksPage = $this->_pageFactory->create()->setData([
            'title' => 'How It Works',
            'page_layout' => '1column',
            'identifier' => 'how-it-works',
            'content_heading' => 'How It Works',
            'content' => '{{widget type="SMG\SPV2HowItWorksPageWidget\Block\Widget\HowItWorksPage" type_name="Scotts Program How It Works Page"}}',
            'is_active' => 1,
            'website_root' => 1,
        ]);

        $this->_pageRepository->save($howItWorksPage);

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

    public function revert()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();

        // Remove the How It Works page if it exists.
        $howItWorksPage = $this->_pageCollection->getItemByColumnValue('identifier', 'how-it-works');
        if ($howItWorksPage) {
            $this->_pageRepository->delete($howItWorksPage);
        }

        $this->_moduleDataSetup->getConnection()->endSetup();
    }
}
