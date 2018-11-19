<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer\GiftCardAccount;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Vantiv\Payment\Gateway\GiftCard\DeactivateCommand;
use Vantiv\Payment\Model\GiftCardAccount\HistoryFactory;
use Magento\Sales\Model\OrderFactory;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;

class DeactivateGiftCard implements ObserverInterface
{
    /**
     * @var string
     */
    const STATUS_FIELD = 'status';

    /**
     * Deactivate command
     *
     * @var \Vantiv\Payment\Gateway\GiftCard\DeactivateCommand
     */
    private $deactivateCommand;

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
     * Vantiv Gift Card settings
     *
     * @var VantivGiftcardConfig
     */
    private $giftcardConfig;

    /**
     * Constructor
     *
     * @param DeactivateCommand $deactivateCommand
     * @param HistoryFactory $historyFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param VantivGiftcardConfig $giftcardConfig
     */
    public function __construct(
        DeactivateCommand $deactivateCommand,
        HistoryFactory $historyFactory,
        OrderFactory $orderFactory,
        VantivGiftcardConfig $giftcardConfig
    ) {
        $this->deactivateCommand = $deactivateCommand;
        $this->historyFactory = $historyFactory;
        $this->orderFactory = $orderFactory;
        $this->giftcardConfig = $giftcardConfig;
    }

    /**
     * Deactivate Gift Card account in Vantiv
     * used for event: magento_giftcardaccount_save_after
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->giftcardConfig->getValue('active')) {
            return;
        }

        /** @var \Magento\GiftCardAccount\Model\Giftcardaccount $giftCardAccount */
        $giftCardAccount = $observer->getEvent()->getGiftcardaccount();

        $currentStatus = $giftCardAccount->getStatus();

        if ($currentStatus == $giftCardAccount->getOrigData(self::STATUS_FIELD) || $giftCardAccount->isObjectNew()) {
            return;
        }

        if ($currentStatus == \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_DISABLED) {
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
                $subject['payment'] = $order->getPayment();
                $subject['giftcard_code'] = $giftCardAccount->getCode();
                $this->deactivateCommand->execute($subject);
            }
        } else {
            throw new LocalizedException(__('Gift Card can be activated during Order creation only'));
        }
    }
}
