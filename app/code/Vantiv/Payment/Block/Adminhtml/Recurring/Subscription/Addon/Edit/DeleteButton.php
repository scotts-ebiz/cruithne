<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Addon\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $buttonData = [];

        if ($this->getAddonId()) {
            $message = __('Are you sure you want to delete this add-on?');

            $buttonData = [
                'label'      => __('Delete'),
                'class'      => 'delete',
                'on_click'   => sprintf("deleteConfirm('%s', '%s')", $message, $this->getDeleteUrl()),
                'sort_order' => 20,
            ];
        }

        return $buttonData;
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/recurring_addon/delete', ['addon_id' => $this->getAddonId()]);
    }
}
