<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring;

abstract class Plan extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var \Vantiv\Payment\Model\Recurring\PlanFactory
     */
    protected $planFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Vantiv\Payment\Model\Recurring\PlanFactory $planFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Vantiv\Payment\Model\Recurring\PlanFactory $planFactory
    ) {
        $this->planFactory = $planFactory;
        parent::__construct($context);
    }

    /**
     * Build Plan Code
     *
     * @param string $code
     * @param integer $productId
     * @return string
     */
    protected function buildCode($code, $productId)
    {
        return $productId . '_' . $code;
    }
}
