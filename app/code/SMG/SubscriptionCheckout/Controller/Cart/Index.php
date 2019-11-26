<?php

namespace SMG\SubscriptionCheckout\Controller\Cart;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Exception\NotFoundException;

class Index extends \Magento\Checkout\Controller\Cart implements HttpGetActionInterface
{

    /**
     * Prevent direct access to the cart
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws NotFoundException
     */
    public function execute()
    {
        throw new NotFoundException(__('404'));   
    }
}
