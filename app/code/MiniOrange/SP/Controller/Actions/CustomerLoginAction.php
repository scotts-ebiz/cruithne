<?php 

namespace MiniOrange\SP\Controller\Actions;

/**
 * This class is called to log the customer user in. RelayState and 
 * user are set separately. This is a simple class.
 */
class CustomerLoginAction extends BaseAction
{
    private $relayState;
	private $user;
	private $customerSession;
	private $responseFactory;

	public function __construct(\Magento\Backend\App\Action\Context $context,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
								\Magento\Customer\Model\Session $customerSession,
								\Magento\Framework\App\ResponseFactory $responseFactory)
	{
        //You can use dependency injection to get any class this observer may need.
		$this->customerSession = $customerSession;
		$this->responseFactory = $responseFactory;
		parent::__construct($context,$spUtility);
	}

	/**
	 * Execute function to execute the classes function. 
	 */
	public function execute()
	{
		$this->customerSession->setCustomerAsLoggedIn($this->user);
		if(!$this->spUtility->isBlank($this->relayState)) $this->relayState = 'customer/account';
		$this->responseFactory->create()
			->setRedirect($this->spUtility->getUrl($this->relayState))
			->sendResponse();
		exit;
	}
	

	/** Setter for the user Parameter */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }


    /** Setter for the RelayState Parameter */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
        return $this;
    } 
}