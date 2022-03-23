<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer\GiftCardAccount;

use Magento\Framework\Event\ObserverInterface;
use Vantiv\Payment\Model\GiftCardAccount\GiftCardAccountFactory;
use Vantiv\Payment\Gateway\GiftCard\SaleCommand;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Magento\Framework\Serialize\Serializer\Json;

class ChargeGiftCard implements ObserverInterface
{
    /**
     * @var GiftCardAccountFactory
     */
    private $giftCardAccountFactory;

    /**
     * Sale command
     *
     * @var SaleCommand
     */
    private $saleCommand;

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
     * Instance of serializer.
     *
     * @var Json
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param GiftCardAccountFactory $giftCardAccountFactory
     * @param SaleCommand $saleCommand
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param VantivGiftcardConfig $giftcardConfig
     * @param Json $json
     */
    public function __construct(
        GiftCardAccountFactory $giftCardAccountFactory,
        SaleCommand $saleCommand,
        PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        VantivGiftcardConfig $giftcardConfig,
        Json $json
    ) {
        $this->giftCardAccountFactory = $giftCardAccountFactory;
        $this->saleCommand = $saleCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->giftcardConfig = $giftcardConfig;
        $this->serializer = $json;
    }

    /**
     * Charge all gift cards applied to the order
     * used for event: sales_order_place_after
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->giftcardConfig->getValue('active')) {
            return $this;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $cards = $this->getOrderCards($order);
        if (is_array($cards)) {
            foreach ($cards as $card) {
                $giftCard = $this->giftCardAccountFactory
                    ->create()
                    ->loadByCode($card[\Magento\GiftCardAccount\Model\Giftcardaccount::CODE]);

                $subject = [];
                $subject['orderId'] = $order->getIncrementId();
                $subject['payment'] = $this->paymentDataObjectFactory->create($order->getPayment());
                $subject['amount'] = $card[\Magento\GiftCardAccount\Model\Giftcardaccount::BASE_AMOUNT];
                $subject['number'] = $giftCard->getCode();
                $this->saleCommand->execute($subject);
            }
        }

        return $this;
    }

    /**
     * Unserialize and return gift card list from Order object
     *
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getOrderCards(\Magento\Sales\Model\Order $order)
    {
        $value = $order->getGiftCards();

        if (!$value) {
            return [];
        }

        return $this->serializer->unserialize($value);
    }
}
