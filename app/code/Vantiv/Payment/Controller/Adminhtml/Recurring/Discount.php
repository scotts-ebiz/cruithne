<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring;

abstract class Discount extends \Magento\Backend\App\Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Vantiv_Payment::subscriptions_actions_edit';

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Vantiv_Payment::subscriptions')
            ->addBreadcrumb(__('Sales'), __('Sales'))
            ->addBreadcrumb(__('Subscriptions'), __('Subscriptions'));

        return $resultPage;
    }
}
