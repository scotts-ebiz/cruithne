<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer\GiftCardAccount;

use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Vantiv\Payment\Gateway\GiftCard\GiftCardStatusCommand;
use Vantiv\Payment\Model\GiftCardAccount\PoolFactory;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;

class ValidateGiftCardBeforeCapture implements ObserverInterface
{
    /**
     * Virtual Gift Card Type
     */
    const TYPE_VIRTUAL = 0;

    /**
     * Gift Card Status command
     *
     * @var GiftCardStatusCommand
     */
    private $giftCardStatusCommand;

    /**
     * Payment data object factory
     *
     * @var PaymentDataObjectFactoryInterface
     */
    private $paymentDataObjectFactory;

    /**
     * Gift Card Pool factory
     *
     * @var PoolFactory
     */
    private $poolFactory;

    /**
     * Vantiv Gift Card settings
     *
     * @var VantivGiftcardConfig
     */
    private $giftcardConfig;

    /**
     * Constructor
     *
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param GiftCardStatusCommand $giftCardStatusCommand
     * @param PoolFactory $poolFactory
     * @param VantivGiftcardConfig $giftcardConfig
     */
    public function __construct(
        PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        GiftCardStatusCommand $giftCardStatusCommand,
        PoolFactory $poolFactory,
        VantivGiftcardConfig $giftcardConfig
    ) {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->giftCardStatusCommand = $giftCardStatusCommand;
        $this->poolFactory = $poolFactory;
        $this->giftcardConfig = $giftcardConfig;
    }

    /**
     * Check gift card code status before invoicing
     * used for event: sales_order_payment_capture
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->giftcardConfig->getValue('active')) {
            return $this;
        }

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();

        $order = $invoice->getOrder();

        $qtyToCheck = 0;

        foreach ($order->getAllItems() as $item) {
            $giftCardType = $item->getProductOptionByCode('giftcard_type');
            if ($giftCardType !== null && $giftCardType != self::TYPE_VIRTUAL) {
                $qtyToCheck += $item->getQtyInvoiced();
            }
        }

        /**
         * No need to continue Gift Cards processing logic, if there are no
         * Gift Card products in the Cart
         */
        if ($qtyToCheck == 0) {
            return $this;
        }

        /** @var \Magento\GiftCardAccount\Model\ResourceModel\Pool\Collection $poolCollection */
        $poolCollection = $this->poolFactory
            ->create()
            ->getCollection()
            ->addFieldToFilter('status', \Magento\GiftCardAccount\Model\Pool\AbstractPool::STATUS_FREE)
            ->setPageSize($qtyToCheck);

        $poolItems = $poolCollection->getItems();

        if ($qtyToCheck > 0 && !$poolItems) {
            throw new LocalizedException(__('No codes left in the pool.'));
        }

        /**
         * Validate each Gift Card code from the Pool
         */
        $subject = [];
        $subject['payment'] = $this->paymentDataObjectFactory->create($order->getPayment());
        /** @var \Magento\GiftCardAccount\Model\Pool $poolItem */
        foreach ($poolItems as $poolItem) {
            $subject['giftcard_code'] = $poolItem->getId();
            $this->giftCardStatusCommand->execute($subject);
        }

        return $this;
    }
}
