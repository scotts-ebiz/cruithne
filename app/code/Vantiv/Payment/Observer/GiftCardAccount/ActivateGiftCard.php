<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer\GiftCardAccount;

use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Vantiv\Payment\Gateway\GiftCard\ActivateCommand;
use Vantiv\Payment\Model\GiftCardAccount\GiftCardAccountFactory;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;

class ActivateGiftCard implements ObserverInterface
{
    /**
     * @var GiftCardAccountFactory
     */
    private $giftCardAccountFactory;

    /**
     * Activate command
     *
     * @var ActivateCommand
     */
    private $activateCommand;

    /**
     * Payment data object factory
     *
     * @var PaymentDataObjectFactoryInterface
     */
    private $paymentDataObjectFactory;

    /**
     * Vantiv Gift Card settings
     *
     * @var VantivGiftcardConfig
     */
    private $giftcardConfig;

    /**
     * Constructor
     *
     * @param GiftCardAccountFactory $giftCardAccountFactory
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param ActivateCommand $activateCommand
     * @param VantivGiftcardConfig $giftcardConfig
     */
    public function __construct(
        GiftCardAccountFactory $giftCardAccountFactory,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Vantiv\Payment\Gateway\GiftCard\ActivateCommand $activateCommand,
        VantivGiftcardConfig $giftcardConfig
    ) {
        $this->giftCardAccountFactory = $giftCardAccountFactory;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->activateCommand = $activateCommand;
        $this->giftcardConfig = $giftcardConfig;
    }

    /**
     * Create gift card account on event
     * used for event: magento_giftcardaccount_create
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->giftcardConfig->getValue('active')) {
            return $this;
        }

        $data = $observer->getEvent()->getRequest();

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $data->getOrderItem();

        $giftCardType = $orderItem->getProductOptionByCode('giftcard_type');

        if ($giftCardType != \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL) {
            $giftCardAccountCodeData = $observer->getEvent()->getCode();
            $giftCardAccount = $this->giftCardAccountFactory->create()->loadByCode($giftCardAccountCodeData->getCode());

            /** @var \Magento\Sales\Model\Order $order */
            $order = $data->getOrder() ?: ($data->getOrderItem()->getOrder() ?: null);

            $subject = [];
            $subject['payment'] = $this->paymentDataObjectFactory->create($order->getPayment());
            $subject['amount'] = $giftCardAccount->getBalance();
            $subject['giftcard_code'] = $giftCardAccountCodeData->getCode();
            $subject['type'] = $giftCardType;

            $this->activateCommand->execute($subject);
        }

        return $this;
    }
}
