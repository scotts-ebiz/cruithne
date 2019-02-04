<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use SMG\CustomerServiceEmail\Model\Order\Email\Container\ServiceTeamIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Email\SenderBuilderFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\SenderBuilder;
use Psr\Log\LoggerInterface;

/**
 * Class OrderFailedSender
 * @package SMG\CustomerServiceEmail\Model\Order\Email\Sender
 */
class OrderFailedSender extends Sender
{
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var Renderer
     */
    protected $addressRenderer;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var ServiceTeamIdentity
     */
    protected $identityContainer;

    /**
     * @param Template $templateContainer
     * @param ServiceTeamIdentity $identityContainer
     * @param SenderBuilderFactory $senderBuilderFactory
     * @param LoggerInterface $logger
     * @param PaymentHelper $paymentHelper
     * @param Renderer $addressRenderer
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Template $templateContainer,
        ServiceTeamIdentity $identityContainer,
        SenderBuilderFactory $senderBuilderFactory,
        LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        ManagerInterface $eventManager
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer
        );

        $this->paymentHelper = $paymentHelper;
        $this->addressRenderer = $addressRenderer;
        $this->eventManager = $eventManager;
        $this->identityContainer = $identityContainer;
    }

    /**
     * Sends cancel order email to the customer.
     *
     * @param Order $order
     * @return bool
     */
    public function send(Order $order)
    {
        return $this->checkAndSend($order);
    }

    /**
     * Sends cancel orders email to the customer.
     *
     * @param OrderInterface[] $orders
     * @return bool
     * @throws \Exception
     */
    public function sendOrders(array $orders)
    {
        if (!$this->identityContainer->isEnabled()) {
            return false;
        }

        $this->prepareOrdersTemplate($orders);
        /** @var SenderBuilder $sender */
        $sender = $this->getSender();

        try {
            $sender->send();
            $sender->sendCopyTo();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Prepare email template with variables
     *
     * @param Order $order
     * @return void
     * @throws \Exception
     */
    protected function prepareTemplate(Order $order)
    {
        $transportObject = new DataObject([
            'order' => $order,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
        ]);

        $this->eventManager->dispatch(
            'failed_email_order_set_template_vars_before',
            ['sender' => $this, 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());
        $this->templateContainer->setTemplateOptions($this->getTemplateOptions());
        $templateId = $this->identityContainer->getTemplateId();

        if ($order->getCustomerIsGuest()) {
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $customerName = $order->getCustomerName();
        }

        $this->identityContainer->setCustomerName($customerName);
        $this->identityContainer->setCustomerEmail($this->identityContainer->getServiceTeamEmails());
        $this->templateContainer->setTemplateId($templateId);
    }

    /**
     * Prepare email orders template with variables
     *
     * @param OrderInterface[] $orders
     * @return void
     * @throws \Exception
     */
    protected function prepareOrdersTemplate(array $orders)
    {
        $transportObject = new DataObject([
            'orders' => $orders
        ]);

        $this->eventManager->dispatch(
            'failed_email_order_set_template_vars_before',
            ['sender' => $this, 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());
        $this->templateContainer->setTemplateOptions($this->getTemplateOptions());
        $templateId = $this->identityContainer->getOrdersTemplateId();

        $this->identityContainer->setCustomerEmail($this->identityContainer->getServiceTeamEmails());
        $this->templateContainer->setTemplateId($templateId);
    }

    /**
     * @param Order $order
     * @return string
     * @throws \Exception
     */
    private function getPaymentHtml(Order $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $this->identityContainer->getStore()->getStoreId()
        );
    }
}
