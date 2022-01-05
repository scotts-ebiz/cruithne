<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\UrlRewriteGraphQl\Model\Resolver;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test the GraphQL endpoint's URLResolver query
 */
class UrlResolverTest extends GraphQlAbstract
{
    /** @var ObjectManager */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Test if UrlResolver will properly resolve the CMS page URL which is a part of hierarchy
     *
     * @magentoApiDataFixture Magento/VersionsCmsUrlRewrite/_files/hierarchy_nodes_with_pages_on_default_store_view_only.php
     */
    public function testExistingEntityUrlRewrite()
    {
        $urlPath = 'page-1/page-2';

        $query = <<<QUERY
{
  urlResolver(url:"{$urlPath}")
  {
   id
   relative_url
   type
   redirectCode
  }
}
QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertEquals('page-2', $response['urlResolver']['relative_url']);
    }
}
