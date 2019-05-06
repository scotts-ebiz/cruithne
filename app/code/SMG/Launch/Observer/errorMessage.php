<?php
namespace SMG\Launch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;

class errorMessage implements ObserverInterface {

	/** @var \SMG\Launch\Model\Session $_launchSession */
	protected $_launchSession;
	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var  \SMG\Launch\Helper\Data $_launchHelper */
	protected $_launchHelper;
	
	protected $request;
	

	public function __construct(
		\SMG\Launch\Model\Session $launchSession,
		 Session $checkoutSession,
		\Magento\Framework\App\Request\Http $request,
		\SMG\Launch\Helper\Data $helper
	) {
		$this->_launchSession = $launchSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_launchHelper = $helper;
		$this->request = $request;
	}

	/**
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return void
	 */
	public function execute( \Magento\Framework\Event\Observer $observer ) {
		
		$action = $this->request->getFullActionName();
		//$erroro = $this->_launchSession->getMessages();
		//$controller = $observer->getControllerAction();
		//$data = $controller->getRequest()->getParam();
		/*echo "<pre>";
		print_r($data);
		exit;*/
		$this->_checkoutSession->setErrorAll('error'); 
		return $this;
	}
}