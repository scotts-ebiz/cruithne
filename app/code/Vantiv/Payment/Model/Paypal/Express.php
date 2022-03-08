<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vantiv\Payment\Model\Paypal;

use Vantiv\Payment\Model\Paypal\Api\Nvp;
use Magento\Paypal\Model\Api\ProcessableException as ApiProcessableException;
use Vantiv\Payment\Model\Paypal\Express\Checkout as ExpressCheckout;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Vantiv\Payment\Gateway\Paypal\AuthorizeCommand;
use Vantiv\Payment\Gateway\Paypal\CaptureCommand;
use Vantiv\Payment\Gateway\Paypal\SaleCommand;
use Vantiv\Payment\Gateway\Paypal\VoidCommand;
use Vantiv\Payment\Gateway\Paypal\CreditCommand;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface as OrderPaymentRepository;
use Magento\Paypal\Model\CartFactory;
use Vantiv\Payment\Model\Paypal\Config as PaypalConfig;
use Magento\Paypal\Model\ProFactory as ProFactory;
use Magento\Framework\DataObject;

/**
 * PayPal Express Module
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Express extends \Magento\Paypal\Model\Express
{
    /**
     * API instance
     *
     * @var \Vantiv\Payment\Model\Paypal\Api\Nvp
     */
    private $api;

    /**
     * @var \Magento\Paypal\Model\Api\Type\Factory
     */
    private $apiFactory = null;

    /**
     * API model type
     *
     * @var string
     */
    private $apiType = 'Vantiv\Payment\Model\Paypal\Api\Nvp';

    /**
     * @var PaypalConfig
     */
    private $config;

    /**
     * Authorize command
     *
     * @var \Vantiv\Payment\Gateway\Paypal\AuthorizeCommand
     */
    private $authorizeCommand;

    /**
     * Capture command
     *
     * @var \Vantiv\Payment\Gateway\Paypal\CaptureCommand
     */
    private $captureCommand;

    /**
     * Sale command
     *
     * @var \Vantiv\Payment\Gateway\Paypal\SaleCommand
     */
    private $saleCommand;

    /**
     * Void command
     *
     * @var \Vantiv\Payment\Gateway\Paypal\VoidCommand
     */
    private $voidCommand;

    /**
     * Credit command
     *
     * @var \Vantiv\Payment\Gateway\Paypal\CreditCommand
     */
    private $creditCommand;

    /**
     * Payment data object factory
     *
     * @var PaymentDataObjectFactoryInterface
     */
    private $paymentDataObjectFactory;

    /**
     * @var string
     */
    protected $_code = PaypalConfig::METHOD_CODE;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var OrderPaymentRepository
     */
    protected $orderPaymentRepository;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $paypalSession;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param ProFactory $proFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param CartFactory $cartFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Exception\LocalizedExceptionFactory $exception
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param \Vantiv\Payment\Gateway\Paypal\AuthorizeCommand $authorizeCommand
     * @param \Vantiv\Payment\Gateway\Paypal\CaptureCommand $captureCommand
     * @param \Vantiv\Payment\Gateway\Paypal\SaleCommand $saleCommand
     * @param \Vantiv\Payment\Gateway\Paypal\VoidCommand $voidCommand
     * @param \Vantiv\Payment\Gateway\Paypal\CreditCommand $creditCommand
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param \Magento\Framework\Session\Generic $paypalSession,
     * @param PaypalConfig $config
     * @param \Magento\Paypal\Model\Api\Type\Factory $apiFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        ProFactory $proFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Paypal\Model\CartFactory $cartFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Exception\LocalizedExceptionFactory $exception,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Vantiv\Payment\Gateway\Paypal\AuthorizeCommand $authorizeCommand,
        \Vantiv\Payment\Gateway\Paypal\CaptureCommand $captureCommand,
        \Vantiv\Payment\Gateway\Paypal\SaleCommand $saleCommand,
        \Vantiv\Payment\Gateway\Paypal\VoidCommand $voidCommand,
        \Vantiv\Payment\Gateway\Paypal\CreditCommand $creditCommand,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        OrderPaymentRepository $orderPaymentRepository,
        \Magento\Framework\Session\Generic $paypalSession,
        PaypalConfig $config,
        \Magento\Paypal\Model\Api\Type\Factory $apiFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $proFactory,
            $storeManager,
            $urlBuilder,
            $cartFactory,
            $checkoutSession,
            $exception,
            $transactionRepository,
            $transactionBuilder,
            $resource,
            $resourceCollection,
            $data
        );
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->authorizeCommand = $authorizeCommand;
        $this->captureCommand = $captureCommand;
        $this->saleCommand = $saleCommand;
        $this->voidCommand = $voidCommand;
        $this->creditCommand = $creditCommand;
        $this->quoteRepository = $quoteRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->paypalSession = $paypalSession;
        $this->config = $config;
        $this->apiFactory = $apiFactory;
        $this->config->setMethod($this->_code);
        $this->_setApiProcessableErrors();
    }

    /**
     * Config instance getter
     *
     * @return PaypalConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * API instance getter
     * Sets current store id to current config instance and passes it to API
     *
     * @return \Vantiv\Payment\Model\Paypal\Api\Nvp
     */
    public function getApi()
    {
        if (null === $this->api) {
            $this->api = $this->apiFactory->create($this->apiType);
        }
        $this->api->setConfigObject($this->config);
        return $this->api;
    }

    /**
     * Set processable error codes to API model
     *
     * @return \Magento\Paypal\Model\Api\Nvp|bool
     */
    protected function _setApiProcessableErrors()
    {

        return ($this->apiFactory !== null) ? $this->getApi()->setProcessableErrors(
            [
                ApiProcessableException::API_INTERNAL_ERROR,
                ApiProcessableException::API_UNABLE_PROCESS_PAYMENT_ERROR_CODE,
                ApiProcessableException::API_DO_EXPRESS_CHECKOUT_FAIL,
                ApiProcessableException::API_UNABLE_TRANSACTION_COMPLETE,
                ApiProcessableException::API_TRANSACTION_EXPIRED,
                ApiProcessableException::API_MAX_PAYMENT_ATTEMPTS_EXCEEDED,
                ApiProcessableException::API_COUNTRY_FILTER_DECLINE,
                ApiProcessableException::API_MAXIMUM_AMOUNT_FILTER_DECLINE,
                ApiProcessableException::API_OTHER_FILTER_DECLINE,
                ApiProcessableException::API_ADDRESS_MATCH_FAIL,
            ]
        ) : false;
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @see \Magento\Sales\Model\Payment::place()
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfig()->getPaymentAction();
    }

    /**
     * Check whether payment method can be used
     * @param \Magento\Quote\Api\Data\CartInterface|Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);

        // for future use in observers

        $this->_eventManager->dispatch(
            'payment_method_is_active',
            [
                'result' => $checkResult,
                'method_instance' => $this,
                'quote' => $quote
            ]
        );

        return $checkResult->getData('is_available') && $this->getConfig()->isMethodAvailable();
    }

    /**
     * Custom getter for payment configuration
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        return $this->getConfig()->getValue($field);
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_placeOrder($payment, $amount);
        $this->execute($this->authorizeCommand, $payment, $amount);

        return $this;
    }

    /**
     * Execute Vantiv API Call
     *
     * @param \Vantiv\Payment\Gateway\Common\AbstractCommand $command
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return void
     * @throws \Exception
     */
    public function execute(
        \Vantiv\Payment\Gateway\Common\AbstractCommand $command,
        \Magento\Payment\Model\InfoInterface $payment,
        $amount = 0.00
    ) {
        try {
            $subject = [];
            $subject['payment'] = $this->paymentDataObjectFactory->create($payment);
            $subject['amount'] = $amount;
            $command->execute($subject);
        } catch (\Exception $e) {
            $this->paypalSession->unsExpressCheckoutToken();
            $this->quoteRepository->save($this->_checkoutSession->getQuote()->setReservedOrderId(null));
            throw $e;
        }
    }

    /**
     * Void payment
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->execute($this->voidCommand, $payment);

        return $this;
    }

    /**
     * Capture payment
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $authorizationTransaction = $payment->getAuthorizationTransaction();
        $authorizationPeriod = abs(intval($this->getConfigData('authorization_honor_period')));
        $maxAuthorizationNumber = abs(intval($this->getConfigData('child_authorization_number')));
        $order = $payment->getOrder();
        $isAuthorizationCreated = false;

        $voided = false;

        if ($authorizationTransaction
            && !$authorizationTransaction->getIsClosed()
            && $this->_isTransactionExpired($authorizationTransaction, $authorizationPeriod)
        ) {
            //Save payment state and configure payment object for voiding
            $isCaptureFinal = $payment->getShouldCloseParentTransaction();
            $payment->setShouldCloseParentTransaction(false);
            $payment->setParentTransactionId($authorizationTransaction->getTxnId());
            $payment->unsTransactionId();
            $payment->setVoidOnlyAuthorization(true);
            $payment->void(new \Magento\Framework\DataObject());

            //Revert payment state after voiding
            $payment->unsAuthorizationTransaction();
            $payment->unsTransactionId();
            $payment->setShouldCloseParentTransaction($isCaptureFinal);
            $voided = true;
        }

        if ($authorizationTransaction && ($authorizationTransaction->getIsClosed() || $voided)) {
            if ($payment->getAdditionalInformation($this->_authorizationCountKey) > $maxAuthorizationNumber - 1) {
                $this->_exception->create(
                    ['phrase' => __('The maximum number of child authorizations is reached.')]
                );
            }

            //Adding authorization transaction
            $this->execute($this->authorizeCommand, $payment, $amount);

            $payment->setParentTransactionId($authorizationTransaction->getParentTxnId());
            $payment->setIsTransactionClosed(false);

            $formatedPrice = $order->getBaseCurrency()->formatTxt($amount);

            if ($payment->getIsTransactionPending()) {
                $message = __(
                    'We\'ll authorize the amount of %1 as soon as the payment gateway approves it.',
                    $formatedPrice
                );
            } else {
                $message = __('The authorized amount is %1.', $formatedPrice);
            }

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($payment->getTransactionId())
                ->setFailSafe(true)
                ->build(Transaction::TYPE_AUTH);
            $payment->addTransactionCommentsToOrder($transaction, $message);

            $payment->setParentTransactionId($payment->getTransactionId());
            $isAuthorizationCreated = true;
        }

        //close order transaction if needed
        if ($payment->getShouldCloseParentTransaction()) {
            $orderTransaction = $this->getOrderTransaction($payment);

            if ($orderTransaction) {
                $orderTransaction->setIsClosed(true);
                $order->addRelatedObject($orderTransaction);
            }
        }

        if (!$payment->getTransactionId()) {
            $this->_placeOrder($payment, $amount);
            $this->execute($this->saleCommand, $payment, $amount);
        } else {
            $this->execute($this->captureCommand, $payment, $amount);
        }

        if ($isAuthorizationCreated && isset($transaction)) {
            $transaction->setIsClosed(true);
        }

        return $this;
    }

    /**
     * Refund capture
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->execute($this->creditCommand, $payment, $amount);

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->void($payment);

        return $this;
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see Quote\Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('vantiv/paypal_express/start');
    }

    /**
     * Place an order with authorization or capture action
     *
     * @param Payment $payment
     * @param float $amount
     * @return $this
     */
    protected function _placeOrder(Payment $payment, $amount)
    {
        $order = $payment->getOrder();

        // prepare api call
        $token = $payment->getAdditionalInformation(ExpressCheckout::PAYMENT_INFO_TRANSPORT_TOKEN);

        $cart = $this->_cartFactory->create(['salesModel' => $order]);

        $api = $this->getApi()->setToken(
            $token
        )->setPayerId(
            $payment->getAdditionalInformation(ExpressCheckout::PAYMENT_INFO_TRANSPORT_PAYER_ID)
        )->setAmount(
            $amount
        )->setNotifyUrl(
            $this->_urlBuilder->getUrl('vantiv/paypal/ipn/')
        )->setInvNum(
            $order->getIncrementId()
        )->setCurrencyCode(
            $order->getBaseCurrencyCode()
        )->setPaypalCart(
            $cart
        )->setIsLineItemsEnabled(
            $this->getConfig()->getValue('lineItemsEnabled')
        );
        if ($order->getIsVirtual()) {
            $api->setAddress($order->getBillingAddress())->setSuppressShipping(true);
        } else {
            $api->setAddress($order->getShippingAddress());
            $api->setBillingAddress($order->getBillingAddress());
        }

        // call api and get details from it
        $api->callDoExpressCheckoutPayment();

        $this->_importToPayment($api, $payment);
        return $this;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture()
    {
        $payment = $this->getInfoInstance();
        $this->getConfig()->setStoreId($payment->getOrder()->getStore()->getId());

        if ($payment->getAdditionalInformation($this->_isOrderPaymentActionKey)) {
            $orderTransaction = $this->getOrderTransaction($payment);
            if ($orderTransaction->getIsClosed()) {
                return false;
            }

            $orderValidPeriod = abs(intval($this->getConfigData('order_valid_period')));

            $dateCompass = new \DateTime($orderTransaction->getCreatedAt());
            $dateCompass->modify('+' . $orderValidPeriod . ' days');
            $currentDate = new \DateTime();

            if ($currentDate > $dateCompass || $orderValidPeriod == 0) {
                return false;
            }
        }

        return $this->_canCapture;
    }

    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        $pathStandardExpress = 'payment/' .  PaypalConfig::METHOD_CODE . '/active';

        return parent::isActive($storeId)
        || (bool)(int)$this->_scopeConfig->getValue($pathStandardExpress, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
