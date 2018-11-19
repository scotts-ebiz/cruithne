<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Catalog\Product\Recurring\Plan\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Vantiv\Payment\Ui\DataProvider\Product\Form\Modifier\RecurringPlans;

class Cancel implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        return [
            'label' => __('Cancel'),
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'product_form.product_form.' . RecurringPlans::CODE_RECURRING_DATA
                                    . '.' . RecurringPlans::CODE_ADD_PLAN_MODAL,
                                'actionName' => 'toggleModal'
                            ]
                        ]
                    ]
                ]
            ],
            'on_click' => ''
        ];
    }
}
