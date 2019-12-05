<?php
namespace SMG\SubscriptionCheckout\Controller;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Captcha\Observer\CaptchaStringResolver;

class AfterPlaceOrderObserver implements ObserverInterface
{

  	protected $_addressRepository;

	public function __construct(
		\Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface
	) {
		$this->_addressRepository = $addressRepositoryInterface;
	}

	/**
	 * Prevent Magento from saving customer's shipping address on order place,
	 * so it always needs to enter it's shipping details on checkout
	 * 
  	 * @param \Magento\Framework\Event\Observer $observer
  	 */
	public function execute(
		\Magento\Framework\Event\Observer $observer
	)
	{
		$order = $observer->getOrder();
    	$customerAddressId =  $order->getShippingAddress()->getCustomerAddressId();
    	$this->_addressRepository->deleteById($customerAddressId);
	}
}