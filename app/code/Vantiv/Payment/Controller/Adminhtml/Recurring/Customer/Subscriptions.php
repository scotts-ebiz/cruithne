<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Customer;

class Subscriptions extends \Magento\Customer\Controller\Adminhtml\Index
{
    const ADMIN_RESOURCE = 'Vantiv_Payment::subscriptions_actions_view';

    /**
     * Customer subscriptions grid
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        return $resultLayout;
    }
}
