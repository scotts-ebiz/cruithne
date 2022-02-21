<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\View;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('vantiv_recurring_subscription_view_tabs');
        $this->setDestElementId('vantiv_recurring_subscription_view');
        $this->setTitle(__('Subscription View'));
    }
}
