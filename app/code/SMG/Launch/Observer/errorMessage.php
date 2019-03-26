<?php
namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class errorMessage implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;

	public function __construct(
		\SMG\Launch\Model\Session $launchSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\SMG\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $launchSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper = $helper;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$CheckoutSession = $objectManager->get('Magento\Checkout\Model\Session');
		$controller = $observer->getControllerAction();
		$data = $controller->getRequest()->getParam();
		echo "<pre>";
		print_r($data);
		exit;
		//$CheckoutSession->setCoupen($couponCode); 
		return $this;
	}
}