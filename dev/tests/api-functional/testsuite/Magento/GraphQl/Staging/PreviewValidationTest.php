<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Staging;

use Magento\Integration\Model\AdminTokenService;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class PreviewValidationTest extends GraphQlAbstract
{
    /**
     * @var AdminTokenService
     */
    private $tokenService;

    protected function setup()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->tokenService = $objectManager->get(AdminTokenService::class);
    }

    /**
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     * @magentoApiDataFixture Magento/Staging/_files/staging_update_tomorrow.php
     * @expectedException \Exception
     * @expectedExceptionMessage Preview is not available for mutations.
     */
    public function testPreviewMutationNotAllowed()
    {
        $version = (string) strtotime('+2 days');
        $headers = $this->getPreviewHeaders($version);

        $query = <<<QUERY
    mutation {
        createEmptyCart
    }
QUERY;
        $this->graphQlMutation($query, [], '', $headers);
    }

    /**
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     * @magentoApiDataFixture Magento/Staging/_files/staging_update_tomorrow.php
     * @expectedException \Exception
     * @expectedExceptionMessage Preview is not available for this query.
     */
    public function testPreviewUnsupportedQuery()
    {
        $version = (string) strtotime('+2 days');
        $headers = $this->getPreviewHeaders($version);

        $query = <<<QUERY
    {
      countries{
        id
        full_name_english
      }
    }
QUERY;
        $this->graphQlQuery($query, [], '', $headers);
    }

    /**
     * @magentoApiDataFixture Magento/User/_files/user_with_custom_role.php
     * @magentoApiDataFixture Magento/Staging/_files/staging_update_tomorrow.php
     * @expectedException \Exception
     * @expectedExceptionMessage Preview-Version must be a valid timestamp.
     */
    public function testPreviewInvalidVersion()
    {
        $headers = $this->getPreviewHeaders('invalid');

        $query = <<<QUERY
    {
      products(filter: {category_id: {eq: "1"}}){
        items{
          id
          sku
          name
        }
      }
    }
QUERY;
        $this->graphQlQuery($query, [], '', $headers);
    }

    /**
     * Get headers to perform preview request
     *
     * @param string $adminUsername
     * @param string $adminPassword
     * @param string $previewVersion
     * @return array
     */
    private function getPreviewHeaders(
        string $previewVersion,
        string $adminUsername = 'customRoleUser',
        string $adminPassword = \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
    ): array {
        return [
            'Authorization' =>
                'Bearer ' . $this->tokenService->createAdminAccessToken($adminUsername, $adminPassword),
            'Preview-Version' => $previewVersion
        ];
    }
}
