<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var \Magento\Directory\Helper\Data
     */
    private $directoryHelper;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        array $data = []
    ) {
        $this->directoryHelper = $directoryHelper;

        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_recurring_subscription';
        $this->_blockGroup = 'Vantiv_Payment';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Subscription'));
        $this->buttonList->remove('delete');


        $script = '
            require([
                "prototype",
                "mage/adminhtml/form"
            ], function() {
                var updater = new RegionUpdater("country_id", "none", "region_id", %s, "disable");
                updater.update();
            });
        ';

        $this->_formScripts[] = sprintf($script, $this->directoryHelper->getRegionJson());
    }

    /*
     * @return string
     */
    public function getBackUrl()
    {
        if ($customerId = $this->getRequest()->getParam('customer_id')) {
            return $this->getUrl('customer/index/edit', ['id' => $customerId]);
        }

        return parent::getBackUrl();
    }
}
