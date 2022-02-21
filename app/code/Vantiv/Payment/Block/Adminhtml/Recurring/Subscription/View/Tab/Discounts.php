<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\View\Tab;

use Magento\Framework\View\Element\Text\ListText;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Discounts extends ListText implements TabInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Discounts');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Subscription Discounts');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
