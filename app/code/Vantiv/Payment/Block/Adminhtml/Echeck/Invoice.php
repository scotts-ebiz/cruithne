<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Echeck;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Vantiv\Payment\Gateway\Echeck\SaleCommand;
use Vantiv\Payment\Gateway\Echeck\Config\VantivEcheckConfig;

/**
 * Invoice creation limiter. JS include block.
 */
class Invoice extends \Magento\Backend\Block\Template
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $registry;
    }

    /**
     * @return string
     */
    public function getMageInitJson()
    {
        return json_encode(
            [
                "#edit_form" => [
                    "Vantiv_Payment/js/echeck-capture-limiter" => [
                        "sale_attempts_count" => $this->getSaleAttemptsCount(),
                        "max_attempts" => $this->getMaxAttempts(),
                        "cancel_url" => $this->getOrderViewUrl()
                    ]
                ]
            ]
        );
    }

    /**
     * @return bool
     */
    public function isEcheckMethod()
    {
        return $this->getPayment()->getMethod() == VantivEcheckConfig::METHOD_CODE;
    }

    /**
     * @return string
     */
    private function getSaleAttemptsCount()
    {
        return $this->getPayment()
            ->getAdditionalInformation(SaleCommand::SALE_ATTEMPTS_KEY);
    }

    /**
     * @return string
     */
    private function getOrderViewUrl()
    {
        return $this->getUrl('sales/order/view', ['order_id' => $this->getOrder()->getId()]);
    }

    /**
     * @return int
     */
    private function getMaxAttempts()
    {
        return SaleCommand::REDEPOSIT_MAX_ATTEMPTS;
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    private function getPayment()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        return $this->getInvoice()->getOrder();
    }

    /**
     * @return \Magento\Sales\Model\Order\Invoice
     * @throws LocalizedException
     */
    private function getInvoice()
    {
        $invoice = $this->coreRegistry->registry('current_invoice');
        if ($invoice) {
            return $invoice;
        }
        throw new LocalizedException(__('Invoice Entity not found.'));
    }
}
