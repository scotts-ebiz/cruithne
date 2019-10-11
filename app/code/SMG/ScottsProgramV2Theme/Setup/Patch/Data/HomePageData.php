<?php

namespace SMG\ScottsProgramV2Theme\Setup\Patch\Data;

use Magento\Cms\Model\PageRepository;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class HomePageData
 *
 * This automatically adds the home page widget to the home page body.
 *
 * @package Magento\DummyModule\Setup\Patch\Data
 */
class HomePageData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    private $pageCollection;
    private $pageRepository;

    /**
     * @param  ModuleDataSetupInterface  $moduleDataSetup
     * @param  PageRepository  $pageRepository
     * @param  PageCollection  $pageCollection
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        PageRepository $pageRepository,
        PageCollection $pageCollection
    ) {
        /**
         * If before, we pass $setup as argument in install/upgrade function, from now we start
         * inject it with DI. If you want to use setup, you can inject it, with the same way as here
         */
        $this->moduleDataSetup = $moduleDataSetup;
        $this->pageCollection = $pageCollection;
        $this->pageRepository = $pageRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $homePage = $this->pageCollection->getItemByColumnValue('identifier', 'home');
        if ($homePage) {
            $homePage->setData('content', '{{widget type="SMG\ScottsProgramV2Theme\Block\Widget\HomePage" heroHeadline="Personalized Lawn Care, Delivered to Your Door" type_name="Scotts Program Home Page"}}');
            $this->pageRepository->save($homePage);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
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
