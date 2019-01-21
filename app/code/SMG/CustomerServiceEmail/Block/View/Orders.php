<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Block\View;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Orders
 * @package SMG\CustomerServiceEmail\Block\View
 */
class Orders extends Template
{
    /**
     * @var Renderer
     */
    private $addressRenderer;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Template\Context $context
     * @param Renderer $addressRenderer
     * @param PaymentHelper $paymentHelper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Order $order
     * @return string|null
     */
    public function getFormattedShippingAddress(Order $order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * @param Order $order
     * @return string|null
     */
    public function getFormattedBillingAddress(Order $order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getPaymentHtml(Order $order)
    {
        try {
            return $this->paymentHelper->getInfoBlockHtml(
                $order->getPayment(),
                $this->storeManager->getStore()->getStoreId()
            );
        } catch (\Exception $e) {}

        return '';
    }
}
