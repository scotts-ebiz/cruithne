<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ElasticsearchCatalogPermissions;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @magentoDbIsolation disabled
 */
class CategoryTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        /** @var \Magento\Framework\Module\ModuleList $modules */
        $modules = $this->_objectManager->get(\Magento\Framework\Module\ModuleList::class);
        if (empty($modules->getOne('Magento_LayeredNavigation'))) {
            $this->markTestSkipped('Skipping test, required module Magento_LayeredNavigation is disabled.');
        }
        if (!$this->isSearchEngineElasticsearch()) {
            $this->markTestSkipped('Skipping test, Elasticsearch must be selected as the search engine');
        }
    }

    /**
     * Tests that fulltext search will respect the permissions applied to a category and will show or hide the
     * products in the respective category
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoDataFixture Magento/ElasticsearchCatalogPermissions/_files/catalog_products.php
     * @magentoDataFixture Magento/CatalogSearch/_files/full_reindex.php
     * @dataProvider searchDataProvider
     * @param string $sku
     * @param string $name
     * @param bool $shouldBeVisible
     */
    public function testPermissibleCategoryProductReturnedByFulltextSearch(
        string $sku,
        string $name,
        bool $shouldBeVisible
    ) {
        $this->performReindex();

        //test successful fulltextsearch
        $this->dispatch('catalogsearch/result/?q=' . $name);
        $responseBody = $this->getResponse()->getBody();
        if ($shouldBeVisible) {
            $this->assertContains(
                $sku . '.html',
                $responseBody,
                'Fulltext search did not return searched product in permissible category'
            );
        } else {
            $this->assertNotContains(
                $sku . '.html',
                $responseBody,
                'Fulltext search returned searched product in denied category'
            );
        }
    }

    /**
     * Tests that layeredNavigation search will respect the permissions applied to a category and will show or
     * hide the products in the respective category
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoDataFixture Magento/ElasticsearchCatalogPermissions/_files/catalog_products.php
     * @param string $sku
     * @param string $name
     * @param bool $shouldBeVisible
     * @dataProvider searchDataProvider
     * @throws \Exception
     */
    public function testPermissibleCategoryProductsReturnedByLayeredNavigationSearch(
        string $sku,
        string $name,
        bool $shouldBeVisible
    ) {
        $this->performReindex();

        $this->dispatch('catalogsearch/advanced/result/?name=' . $name);
        $responseBody = $this->getResponse()->getBody();
        if ($shouldBeVisible) {
            $this->assertContains(
                $sku . '.html',
                $responseBody,
                'LayeredNavigation search did not return searched product in permissible category'
            );
        } else {
            $this->assertNotContains(
                $sku . '.html',
                $responseBody,
                'LayeredNavigation search returned searched product in denied category'
            );
        }
    }

    /**
     * Data provider returning sku, name, and whether the guest should be allowed to see the product
     * @return array
     */
    public function searchDataProvider()
    {
        return [
            ['simple_allow_122', 'Allow category product', true],
            ['simple_deny_122', 'Deny category product', false]
        ];
    }

    /**
     * Performs reindex because of permissions created in fixture
     * @throws \Exception
     */
    private function performReindex()
    {
        /** @var  $indexer \Magento\Framework\Indexer\IndexerInterface */
        $indexer = $this->_objectManager->create(\Magento\Indexer\Model\Indexer::class);

        $indexer->load(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID);
        $indexer->reindexAll();
    }

    /**
     * Checks if application is configured to use ElasticSearch as search engine
     * @return bool
     */
    private function isSearchEngineElasticsearch()
    {
        /** @var ScopeConfigInterface $config */
        $config = $this->_objectManager->get(ScopeConfigInterface::class);
        $searchEngine = $config->getValue('catalog/search/engine');

        return strpos($searchEngine, 'elasticsearch') !== false;
    }
}
