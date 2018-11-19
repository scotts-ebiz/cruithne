<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Payment;

use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vantiv\Payment\Helper\Recurring as RecurringHelper;
use Vantiv\Payment\Model\BatchResponse\Item\AbstractHandler;
use Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus;

class Handler extends AbstractHandler
{
    /**
     * @var \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig
     */
    private $config;

    /**
     * @var \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParserFactory
     */
    private $parserFactory;

    /**
     * @var \Vantiv\Payment\Model\Recurring\SubscriptionFactory
     */
    private $subscriptionFactory;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Subscription\ToOrder
     */
    private $subscriptionToOrder;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Subscription\ToOrderItem
     */
    private $subscriptionToOrderItem;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Subscription\ToOrderPayment
     */
    private $subscriptionToOrderPayment;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Subscription\Address\ToOrderAddress
     */
    private $subscriptionAddressToOrderAddress;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $transactionBuilder;

    /**
     * @var \Magento\Sales\Model\Order\Payment\State\CaptureCommand
     */
    private $paymentStateCaptureCommand;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderEmailSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender
     */
    private $orderCommentSender;

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
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender
    ) {
        parent::__construct($emailTransportBuilder, $inlineTranslation, $logger);
        $this->config = $config;
        $this->parserFactory = $parserFactory;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->subscriptionToOrder = $subscriptionToOrder;
        $this->subscriptionToOrderItem = $subscriptionToOrderItem;
        $this->subscriptionToOrderPayment = $subscriptionToOrderPayment;
        $this->subscriptionAddressToOrderAddress = $subscriptionAddressToOrderAddress;
        $this->orderFactory = $orderFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->quoteFactory = $quoteFactory;
        $this->transactionBuilder = $transactionBuilder;
        $this->paymentStateCaptureCommand = $paymentStateCaptureCommand;
        $this->orderEmailSender = $orderEmailSender;
        $this->orderCommentSender = $orderCommentSender;
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
        if (!$parser->getLitleTxnId() || !$parser->getRecurringResponseSubscriptionId()) {
            $this->logError(
                "Error importing payment: saleResponse missing required info, xml was:\n" . $saleResponse->asXML()
            );
            return;
        }

        $subscription = $this->getSubscriptionFactory()->create()
            ->load($parser->getRecurringResponseSubscriptionId(), 'vantiv_subscription_id');
        if (!$subscription->getId()) {
            $this->logError("Error importing payment: subscription not found, xml was:\n" . $saleResponse->asXML());
            return;
        }

        $sendOrderConfirmationEmail = false;
        $isSuccessful = $parser->isSuccessful();
        $lastOrder = $subscription->getLastOrder();
        if ($lastOrder && $lastOrder->getStatus() == RecurringHelper::PENDING_RECURRING_PAYMENT_ORDER_STATUS) {
            $order = $lastOrder;
        } else {
            $order = $this->createNewOrder($subscription, $parser);
            $sendOrderConfirmationEmail = true;
        }

        try {
            $saveOrder = true;
            if ($isSuccessful) {
                $this->processSuccessfullPayment($order, $parser);
            } else {
                if (!$order->getId()) {
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                        ->setStatus(RecurringHelper::PENDING_RECURRING_PAYMENT_ORDER_STATUS);
                } else {
                    $saveOrder = false;
                }

                if ($subscription->getStatus() != SubscriptionStatus::SUSPENDED) {
                    $orderCommentToSend = __(
                        'Recurring payment failed, reason - %1. '
                        . 'Please review and update subscription payment details from corresponding '
                        . 'My Account section.',
                        __($parser->getMessage())
                    );
                    $order->addStatusHistoryComment($orderCommentToSend)
                        ->setIsCustomerNotified(true);
                    $saveOrder = true;

                    $subscription->setStatus(SubscriptionStatus::SUSPENDED)
                        ->setSkipSendingToVantiv(true)
                        ->save();
                }
            }

            if ($saveOrder) {
                $order->save();
            }
        } catch (\Exception $e) {
            $sendOrderConfirmationEmail = false;
            $this->logError(
                "Error importing payment: exception occurred while preparing/saving order\n" . $e->getMessage()
                . "\nxml was:\n" . $saleResponse->asXML()
            );
        }

        if ($sendOrderConfirmationEmail) {
            try {
                $this->orderEmailSender->send($order);
            } catch (\Exception $e) {
                $this->getLogger()->error($e);
            }
        }

        if (isset($orderCommentToSend)) {
            try {
                $this->getOrderCommentSender()->send($order, true, $orderCommentToSend);
            } catch (\Exception $e) {
                $this->getLogger()->error($e);
            }
        }
    }

    /**
     * @return \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParserFactory
     */
    protected function getParserFactory()
    {
        return $this->parserFactory;
    }

    /**
     * @return \Vantiv\Payment\Model\Recurring\SubscriptionFactory
     */
    protected function getSubscriptionFactory()
    {
        return $this->subscriptionFactory;
    }

    /**
     * @return \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender
     */
    protected function getOrderCommentSender()
    {
        return $this->orderCommentSender;
    }

    /**
     * Create new order based on subscription data
     *
     * @param \Vantiv\Payment\Model\Recurring\Subscription $subscription
     * @param \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    private function createNewOrder(
        \Vantiv\Payment\Model\Recurring\Subscription $subscription,
        \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser
    ) {
        $subscriptionClone = clone $subscription;
        $responseTime = false;
        try {
            if (!$parser->getResponseTime()) {
                throw new \Exception('Response time not set.');
            }
            $responseTime = new \DateTime($parser->getResponseTime());
        } catch (\Exception $e) {
            $this->logError(
                "Error importing payment: order amounts cannot be updated, cannot read responseTime\n"
                . $e->getMessage() . "\nxml was:\n" . $parser->toXML()
            );
        }

        if ($responseTime) {
            $subscriptionClone->recalculateAmountsToDate($responseTime);
        }

        $order = $this->subscriptionToOrder->convert($subscriptionClone);

        $addresses = [];
        $billingAddress = $this->subscriptionAddressToOrderAddress->convert(
            $subscriptionClone->getBillingAddress(),
            [
                'address_type' => 'billing'
            ]
        );
        $addresses[] = $billingAddress;
        $order->setBillingAddress($billingAddress);
        if (!$subscriptionClone->getIsVirtual()) {
            $shippingAddress = $this->subscriptionAddressToOrderAddress->convert(
                $subscriptionClone->getShippingAddress(),
                [
                    'address_type' => 'shipping'
                ]
            );
            $addresses[] = $shippingAddress;
            $order->setShippingAddress($shippingAddress);
        }
        $order->setAddresses($addresses);

        $order->setPayment($this->subscriptionToOrderPayment->convert($subscriptionClone, $order));
        $order->setItems([$this->subscriptionToOrderItem->convert($subscriptionClone, $order)]);

        $order->setIncrementId(
            $this->quoteFactory->create()
                ->setStoreId($subscriptionClone->getStoreId())
                ->reserveOrderId()
                ->getReservedOrderId()
        );

        $order->setIsCustomerNotified(true);

        return $order;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser
     * @return $this
     */
    protected function processSuccessfullPayment(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Vantiv\Payment\Gateway\Recurring\Parser\RecurringSaleResponseParser $parser
    ) {
        $payment = $order->getPayment();

        // create invoice
        foreach ($order->getAllItems() as $orderItem) {
            $orderItem->setLockedDoInvoice(false);
        }
        $invoice = $order->prepareInvoice();
        $order->addRelatedObject($invoice);
        $invoice->register()
            ->setIsPaid(true)
            ->pay();
        $payment->setAmountAuthorized($order->getTotalDue())
            ->setBaseAmountAuthorized($order->getBaseTotalDue())
            ->setBaseAmountPaidOnline($invoice->getBaseGrandTotal());

        // create capture transaction
        $transactionBuilder = $this->transactionBuilder->setPayment($order->getPayment());
        $transactionBuilder->setOrder($order);
        $transactionBuilder->setFailSafe(true);
        $transactionBuilder->setTransactionId($parser->getLitleTxnId());
        $transactionBuilder->setAdditionalInformation($payment->getTransactionAdditionalInfo());
        $transactionBuilder->setSalesDocument($invoice);
        $transaction = $transactionBuilder->build(Transaction::TYPE_CAPTURE);
        $message = $this->paymentStateCaptureCommand
            ->execute($payment, $invoice->getBaseGrandTotal(), $order);
        $payment->addTransactionCommentsToOrder($transaction, $message);
        $invoice->setTransactionId($payment->getLastTransId());

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function getErrorEmailRecipient()
    {
        return $this->config->getValue('error_email_recipient', $this->websiteId, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @inheritdoc
     */
    protected function getErrorEmailSender()
    {
        return $this->config->getValue('error_email_sender', $this->websiteId, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @inheritdoc
     */
    protected function getErrorEmailTemplate()
    {
        return $this->config->getValue('error_email_template', $this->websiteId, ScopeInterface::SCOPE_WEBSITE);
    }
}
