<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Model\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use SMG\CustomerServiceEmail\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Email\SenderBuilderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class OrderCancelSender
 * @package SMG\CustomerServiceEmail\Model\Order\Email\Sender
 */
class OrderCancelSender extends Sender
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
     * @param Template $templateContainer
     * @param OrderIdentity $identityContainer
     * @param SenderBuilderFactory $senderBuilderFactory
     * @param LoggerInterface $logger
     * @param PaymentHelper $paymentHelper
     * @param Renderer $addressRenderer
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Template $templateContainer,
        OrderIdentity $identityContainer,
        SenderBuilderFactory $senderBuilderFactory,
        LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        ManagerInterface $eventManager
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->addressRenderer = $addressRenderer;
        $this->eventManager = $eventManager;

        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer
        );
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
        // create return value
        $returnVal = true;

        //loop through the orders and send cancellation
        foreach ($orders as $order)
        {
            try
            {
                $this->checkAndSend($order);
            }
            catch (\Exception $e)
            {
                $this->logger->error($e->getMessage());

                $returnVal = false;
            }
        }

        return $returnVal;
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
            'cancel_email_order_set_template_vars_before',
            ['sender' => $this, 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        parent::prepareTemplate($order);
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
