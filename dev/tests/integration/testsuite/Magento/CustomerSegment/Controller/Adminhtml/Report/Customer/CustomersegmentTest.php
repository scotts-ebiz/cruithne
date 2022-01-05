<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Report\Customer;

/**
 * @magentoAppArea adminhtml
 */
class CustomersegmentTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @inheritDoc
     */
    protected $resource = 'Magento_CustomerSegment::segment';
    /**
     * @inheritDoc
     */
    protected $uri = 'backend/customersegment/report_customer_customersegment/Segment';
    /**
     * Checks if child 'grid' block is found in Magento_CustomerSegment::report/detail/grid/container.phtml
     *
     * @magentoDataFixture Magento/CustomerSegment/_files/segment.php
     */
    public function testSegmentAction()
    {
        $segment = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\CustomerSegment\Model\Segment::class
        );
        $segment->load('Customer Segment 1', 'name');

        $this->dispatch(
            'backend/customersegment/report_customer_customersegment/detail/segment_id/' . $segment->getId()
        );
        $content = $this->getResponse()->getBody();
        $this->assertContains('segmentGridJsObject', $content);
    }
}
