<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Plugin\GiftCardAccount\Model;

use Vantiv\Payment\Gateway\GiftCard\GiftCardBalanceInquiryCommand;
use Vantiv\Payment\Model\GiftCardAccount\HistoryFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;

/**
 * Class GiftCardAccount
 */
class GiftCardAccount
{
    /**
     * @var GiftCardBalanceInquiryCommand
     */
    private $balanceInquiryCommand;

    /**
     * History factory
     *
     * @var HistoryFactory
     */
    private $historyFactory = null;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

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
     * @param GiftCardBalanceInquiryCommand $balanceInquiryCommand
     * @param HistoryFactory $historyFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param VantivGiftcardConfig $giftcardConfig
     */
    public function __construct(
        GiftCardBalanceInquiryCommand $balanceInquiryCommand,
        HistoryFactory $historyFactory,
        OrderFactory $orderFactory,
        PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        VantivGiftcardConfig $giftcardConfig
    ) {
        $this->balanceInquiryCommand = $balanceInquiryCommand;
        $this->historyFactory = $historyFactory;
        $this->orderFactory = $orderFactory;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->giftcardConfig = $giftcardConfig;
    }

    /**
     * Execute BalanceInquiry Vantiv Balance Check
     *
     * @param \Magento\GiftCardAccount\Model\Giftcardaccount $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsValid(
        \Magento\GiftCardAccount\Model\Giftcardaccount $subject,
        $result
    ) {
        if (!$this->giftcardConfig->getValue('active')) {
            return $result;
        }

        $giftCardAccount = $subject;

        $giftCardAccountHistory = $this->historyFactory
            ->create()
            ->getCollection()
            ->addFieldToFilter('giftcardaccount_id', $giftCardAccount->getId())
            ->getFirstItem();

        $orderData = $giftCardAccountHistory->getAdditionalInfo();

        if ($orderData) {
            $orderId = preg_match('/#(.*?)\./', $orderData, $match) ? $match[1] : false;
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            $subject = [];
            $subject['payment'] = $this->paymentDataObjectFactory->create($order->getPayment());
            $subject['giftcard_code'] = $giftCardAccount->getCode();
            $subject['giftCardAccount'] = $giftCardAccount;
            $this->balanceInquiryCommand->execute($subject);
        }

        return $result;
    }
}
