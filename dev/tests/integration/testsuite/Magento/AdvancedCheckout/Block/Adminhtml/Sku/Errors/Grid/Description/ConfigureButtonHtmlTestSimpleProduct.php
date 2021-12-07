<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\Description;

/**
 * Checks configure button appearance for simple product
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ConfigureButtonHtmlTestSimpleProduct extends AbstractConfigureButtonHtmlTest
{
    /**
     * Check button rendering for simple product
     *
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     *
     * @return void
     */
    public function testGetConfigureButtonHtmlSimpleProduct(): void
    {
        $this->prepareBlock('simple2');
        $result = $this->block->getConfigureButtonHtml();
        $this->assertContains('disabled="disabled', $result);
        $this->assertNotContains('onclick', $result);
        $this->assertContains((string)__('Configure'), strip_tags($result));
    }
}
