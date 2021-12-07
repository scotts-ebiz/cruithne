<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Block\Adminhtml\Banner;

use Magento\Mtf\Client\Locator;

/**
 * Class BannerForm
 * Backend banner form
 */
class BannerForm extends \Magento\Banner\Test\Block\Adminhtml\Banner\BannerForm
{
    /**
     * Locator for customer segment
     *
     * @var string
     */
    protected $useSegment = '//div[@data-index="customer_segment_ids"]/div/div';

    /**
     * Locator for apply banner to the Selected Customer Segments
     *
     * @var string
     */
    protected $customerSegmentOptions = '//div[@data-index="customer_segment_ids"]//li/div';

    /**
     * Check whether customer segment is available on Banner form
     *
     * @param string $customerSegment
     * @return bool
     */
    public function isCustomerSegmentVisible($customerSegment)
    {
        $this->_rootElement->find($this->useSegment, Locator::SELECTOR_XPATH)->click();
        $segments = $this->_rootElement->getElements($this->customerSegmentOptions, Locator::SELECTOR_XPATH);
        foreach ($segments as $segment) {
            if ($customerSegment == $segment->getText()) {
                return true;
            }
        }
        return false;
    }
}
