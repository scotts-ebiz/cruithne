<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

use Magento\Sales\Model\Config\Source\Order\Status\Newprocessing as OrderStatusSource;

/**
 * Order Status options Source Model.
 */
class OrderStatus implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var OrderStatusSource
     */
    private $orderStatusSource;

    /**
     * @param OrderStatusSource $orderStatusSource
     */
    public function __construct(OrderStatusSource $orderStatusSource)
    {
        $this->orderStatusSource = $orderStatusSource;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->orderStatusSource->toOptionArray();
    }
}
