<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer\GiftCardAccount;

use Magento\Framework\Event\ObserverInterface;
use Vantiv\Payment\Model\GiftCardAccount\GiftCardAccountFactory;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Vantiv\Payment\Gateway\GiftCard\ActivateCommand;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;

class GiftCardAccountSaveBefore implements ObserverInterface
{
    /**
     * @var GiftCardAccountFactory
     */
    private $giftCardAccountFactory;

    /**
     * @var VantivGiftcardConfig
     */
    private $config;

    /**
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
     * Constructor
     *
     * @param GiftCardAccountFactory $giftCardAccountFactory
     * @param VantivGiftcardConfig $config
     * @param ActivateCommand $activateCommand
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     */
    public function __construct(
        GiftCardAccountFactory $giftCardAccountFactory,
        VantivGiftcardConfig $config,
        ActivateCommand $activateCommand,
        PaymentDataObjectFactoryInterface $paymentDataObjectFactory
    ) {
        $this->giftCardAccountFactory = $giftCardAccountFactory;
        $this->config = $config;
        $this->activateCommand = $activateCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * Save Virtual Gift Card Account number
     * used for event: magento_giftcardaccount_save_before
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->getValue('active')) {
            return $this;
        }

        /** @var \Magento\GiftCardAccount\Model\Giftcardaccount $giftCardAccount */
        $giftCardAccount = $observer->getEvent()->getGiftcardaccount();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $giftCardAccount->getOrder();

        if ($order === null) {
            return $this;
        }

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $order->getItemsCollection()->getFirstItem();

        $subject = [];
        $subject['type'] = $orderItem->getProductOptionByCode('giftcard_type');

        if ($subject['type'] !== null && (int) $subject['type'] === \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL) {
            $subject['amount'] = $giftCardAccount->getBalance();
            $subject['giftCardBin'] = $this->config->getValue('bin');
            $subject['accountNumberLength'] = $this->config->getValue('pan_length');
            $subject['payment'] = $this->paymentDataObjectFactory->create($order->getPayment());
            $subject['giftCardAccount'] = $giftCardAccount;
            $this->activateCommand->execute($subject);
        }

        return $this;
    }
}
