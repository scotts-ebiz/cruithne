<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\RecoveryTransaction;

use \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus;
use \Vantiv\Payment\Model\Recurring\Source\RecoveryTransactionStatus;

class Handler extends \Vantiv\Payment\Model\Recurring\Payment\Handler
{
    /**
     * @var \Vantiv\Payment\Model\Recurring\RecoveryTransactionFactory
     */
    private $recoveryTransactionFactory;

    /**
     * @param \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $config
     * @param \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Mail\Template\TransportBuilder $emailTransportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Vantiv\Payment\Model\Recurring\Subscription\ToOrder $subscriptionToOrder
     * @param \Vantiv\Payment\Model\Recurring\Subscription\ToOrderItem $subscriptionToOrderItem
     * @param \Vantiv\Payment\Model\Recurring\Subscription\ToOrderPayment $subscriptionToOrderPayment
     * @param \Vantiv\Payment\Model\Recurring\Subscription\Address\ToOrderAddress $subscriptionAddressToOrderAddress
     * @param \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Sales\Model\Order\Payment\State\CaptureCommand $paymentStateCaptureCommand
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender
     * @param \Vantiv\Payment\Model\Recurring\RecoveryTransactionFactory $recoveryTransactionFactory
     */
    public function __construct(
        \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $config,
        \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParserFactory $parserFactory,
        \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Mail\Template\TransportBuilder $emailTransportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Vantiv\Payment\Model\Recurring\Subscription\ToOrder $subscriptionToOrder,
        \Vantiv\Payment\Model\Recurring\Subscription\ToOrderItem $subscriptionToOrderItem,
        \Vantiv\Payment\Model\Recurring\Subscription\ToOrderPayment $subscriptionToOrderPayment,
        \Vantiv\Payment\Model\Recurring\Subscription\Address\ToOrderAddress $subscriptionAddressToOrderAddress,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Payment\State\CaptureCommand $paymentStateCaptureCommand,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender,
        \Vantiv\Payment\Model\Recurring\RecoveryTransactionFactory $recoveryTransactionFactory
    ) {
        parent::__construct(
            $config,
            $parserFactory,
            $subscriptionFactory,
            $logger,
            $emailTransportBuilder,
            $inlineTranslation,
            $subscriptionToOrder,
            $subscriptionToOrderItem,
            $subscriptionToOrderPayment,
            $subscriptionAddressToOrderAddress,
            $orderFactory,
            $dataObjectHelper,
            $quoteFactory,
            $transactionBuilder,
            $paymentStateCaptureCommand,
            $orderEmailSender,
            $orderCommentSender
        );
        $this->recoveryTransactionFactory = $recoveryTransactionFactory;
    }

    /**
     * @param \SimpleXMLElement $saleResponse
     */
    public function handle(\SimpleXMLElement $saleResponse)
    {
        if (!$saleResponse->getName() == 'saleResponse') {
            return;
        }

        /** @var \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser */
        $parser = $this->getParserFactory()->create(['xml' => $saleResponse]);
        if (!$parser->getLitleTxnId() || !$parser->getOrderId()) {
            $this->logError(
                "Error importing recovery transaction: saleResponse missing required info, xml was:\n"
                . $saleResponse->asXML()
            );
            return;
        }

        $subscription = $this->getSubscriptionFactory()->create()
            ->load($parser->getOrderId(), 'original_order_increment_id');
        if (!$subscription->getId()) {
            $this->logError(
                "Error importing recovery transaction: corresponding subscription not found, xml was:\n"
                . $saleResponse->asXML()
            );
            return;
        }

        $isSuccessful = $parser->isSuccessful();
        if ($isSuccessful) {
            $this->processSuccessfulRecovery($subscription, $parser);
        } else {
            $this->processFailedRecovery($subscription, $parser);
        }

        try {
            $this->recoveryTransactionFactory->create()
                ->setLitleTxnId($parser->getLitleTxnId())
                ->setReportGroup($parser->getReportGroup())
                ->setSubscriptionId($subscription->getId())
                ->setResponseCode($parser->getResponse())
                ->setResponseMessage($parser->getMessage())
                ->setStatus($isSuccessful ? RecoveryTransactionStatus::APPROVED : RecoveryTransactionStatus::DECLINED)
                ->save();
        } catch (\Exception $e) {
            $this->logError(
                "Error importing recovery transaction: exception occurred while saving transaction details"
                . ", xml was:\n" . $saleResponse->asXML() . "\n\n exception message: "
                . $e->getMessage()
            );
        }
    }

    /**
     * Process successful recovery transaction
     *
     * @param \Vantiv\Payment\Model\Recurring\Subscription $subscription
     * @param \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser
     * @return $this
     */
    private function processSuccessfulRecovery(
        \Vantiv\Payment\Model\Recurring\Subscription $subscription,
        \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser
    ) {
        $lastOrder = $subscription->getLastOrder();
        if (!($lastOrder
            && $lastOrder->getStatus() == \Vantiv\Payment\Helper\Recurring::PENDING_RECURRING_PAYMENT_ORDER_STATUS)
        ) {
            $this->logError(
                "Error importing recovery transaction: unable to find pending order to update with "
                . "recovery status, xml was:\n" . $parser->toXml()
            );
            return $this;
        }

        try {
            $this->processSuccessfullPayment($lastOrder, $parser);
            $lastOrder->save();
        } catch (\Exception $e) {
            $this->logError(
                "Error importing recovery transaction: exception occurred while updating the order,"
                . " xml was:\n" . $parser->toXML() . "\n\n exception message: "
                . $e->getMessage()
            );
        }

        if ($subscription->getStatus() == SubscriptionStatus::SUSPENDED) {
            $subscription->setStatus(SubscriptionStatus::ACTIVE)
                ->setSkipSendingToVantiv(true)
                ->save();
        }

        return $this;
    }

    /**
     * Process failed recovery transaction
     *
     * @param \Vantiv\Payment\Model\Recurring\Subscription $subscription
     * @param \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser
     * @return $this
     */
    private function processFailedRecovery(
        \Vantiv\Payment\Model\Recurring\Subscription $subscription,
        \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser
    ) {
        if ($subscription->getStatus() == SubscriptionStatus::CANCELLED) {
            return $this;
        }

        $lastOrder = $subscription->getLastOrder();
        if (!($lastOrder
            && $lastOrder->getStatus() == \Vantiv\Payment\Helper\Recurring::PENDING_RECURRING_PAYMENT_ORDER_STATUS)
        ) {
            $this->logError(
                "Error importing recovery transaction: unable to find pending order to update with "
                . "recovery status, xml was:\n" . $parser->toXml()
            );
            return $this;
        }

        try {
            $subscription->setStatus(SubscriptionStatus::CANCELLED)
                ->save();
        } catch (\Exception $e) {
            $this->logError(
                "Error importing recovery transaction: exception occurred while updating subscription "
                . "status, xml was:\n" . $parser->toXML() . "\n\n exception message: " . $e->getMessage()
            );
            return $this;
        }

        try {
            $orderCommentToSend = __(
                'Recurring payment recovery failed, reason - %1. '
                . 'Your subscription for "%2" has been automatically cancelled.',
                __($parser->getMessage()),
                $subscription->getProductName()
            );
            $lastOrder->addStatusHistoryComment($orderCommentToSend)
                ->setIsCustomerNotified(true);
            $lastOrder->save();
        } catch (\Exception $e) {
            $this->logError(
                "Error importing recovery transaction: exception occurred while updating the order,"
                . " xml was:\n" . $parser->toXML() . "\n\n exception message: "
                . $e->getMessage()
            );
        }

        try {
            $this->getOrderCommentSender()->send($lastOrder, true, $orderCommentToSend);
        } catch (\Exception $e) {
            $this->getLogger()->error($e);
        }

        return $this;
    }
}
