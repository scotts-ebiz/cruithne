<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\Sales\Block\Adminhtml\Order\Creditmemo\Create;

/**
 * Adminhtml credit memo items grid
 *
 * @api
 * @since 100.0.2
 */
class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{
    /**
     * Prepare child blocks
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('creditmemo_item_container'),'" . $this->getUpdateUrl() . "')";
        $this->addChild(
            'update_button',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => __('Update Qty\'s'), 'class' => 'update-button', 'onclick' => $onclick]
        );

        if ($this->getCreditmemo()->canRefund())
        {
            $this->addChild(
                'submit_button',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Refund'),
                    'class' => 'save submit-button refund primary',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemo()'
                ]
            );

            $this->addChild(
                'submit_offline',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Refund Offline'),
                    'class' => 'save submit-button primary',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()'
                ]
            );
        }
        else
        {
            $this->addChild(
                'submit_button',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Refund Offline'),
                    'class' => 'save submit-button primary',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()'
                ]
            );
        }

        // Need to call the prepareLayout method in the parent's parent.
        return call_user_func(array(get_parent_class(get_parent_class($this)), '_prepareLayout'));
    }
}
