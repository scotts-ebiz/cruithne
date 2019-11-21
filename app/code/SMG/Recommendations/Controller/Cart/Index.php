<?php

namespace SMG\Recommendations\Controller\Cart;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use \Magento\Framework\Exception\NotFoundException;

class Index extends \Magento\Checkout\Controller\Cart implements HttpGetActionInterface
{
   
    public function execute()
    {
        throw new NotFoundException(__('404'));   
    }
}
