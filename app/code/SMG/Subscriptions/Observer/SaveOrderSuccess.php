<?php

namespace SMG\Subscriptions\Observer;

use \Psr\Log\LoggerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class SaveOrderSuccess implements \Magento\Framework\Event\ObserverInterface
{

	protected $_logger;
    protected $_order;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\Data\OrderInterface $order
    )
    {        
        $this->_logger = $logger;
        $this->_order = $order;
    }


	public function execute(\Magento\Framework\Event\Observer $observer) {
        orderids = $observer->getEvent()->getOrderIds();

        foreach($orderids as $orderid){
            $order = $this->_order->load($orderid);
            print_r( $order );
            echo '<p>---</p>';
        }

        print_r( $_POST ); die();
        // $controller = $observer->getControllerAction();

        // $this->_logger->debug($controller->getRequest()->getPost());
	}
}