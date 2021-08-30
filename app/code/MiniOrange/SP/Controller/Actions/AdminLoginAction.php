<?php 

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use MiniOrange\SP\Helper\SPConstants;

/**
 * This class is called from the observer class to log the 
 * admin user in. Read the appropriate values required from the 
 * requset parameter passed along with the redirect to log the user in.
 * <b>NOTE</b> : Admin ID, Session Index and relaystate are passed 
 *              in the request parameter.
 */
class AdminLoginAction extends BaseAction
{
    private $relayState;
    private $user;
    private $adminSession;
    private $cookieManager;
    private $adminConfig;
    private $cookieMetadataFactory;
    private $adminSessionManager;
    private $urlInterface;
    private $userFactory;
    private $request;

    public function __construct(\Magento\Backend\App\Action\Context $context,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \Magento\Backend\Model\Auth\Session $adminSession,
                                \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
                                \Magento\Backend\Model\Session\AdminConfig $adminConfig,
                                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
                                \Magento\Security\Model\AdminSessionsManager $adminSessionManager,
                                \Magento\Backend\Model\UrlInterface $urlInterface,
                                \Magento\User\Model\UserFactory $userFactory,
                                \Magento\Framework\App\RequestInterface $request)
	{
        //You can use dependency injection to get any class this observer may need.
        $this->adminSession = $adminSession;
        $this->cookieManager = $cookieManager;
        $this->adminConfig =$adminConfig;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->adminSessionManager = $adminSessionManager;
        $this->urlInterface = $urlInterface;
        $this->userFactory = $userFactory;
        $this->request = $request;
		parent::__construct($context,$spUtility);
	}

	/**
	 * Execute function to execute the classes function.
     * Check if valid request by checking the SESSION_INDEX in the request
     * and the session index in the database. If they don't match then return
     * This is done to take care of the backdoor that this URL creates if no
     * session index is checked
     *
     * @throws FailureToSendException If cookie couldn't be sent to the browser.
     * @throws CookieSizeLimitReachedException Thrown when the cookie is too big to store any additional data.
     * @throws InputException If the cookie name is empty or contains invalid characters.
	 */
	public function execute()
	{
        $params = $this->request->getParams(); // get request params
        $sessionIndex = $this->spUtility->getAdminStoreConfig(SPConstants::SESSION_INDEX,$params['userid']);
        $sessionIndexInRequest = $params['sessionindex'];
        if(strcasecmp($sessionIndex,$sessionIndexInRequest)!=0) return;
        $user = $this->userFactory->create()->load($params['userid']);
        $this->adminSession->setUser($user);
        $this->adminSession->processLogin();
        if($this->adminSession->isLoggedIn())
        {
            $cookieValue = $this->adminSession->getSessionId();
            if ($cookieValue)
            {
                // generate admin cookie value - this is required to create a valid admin session
                $cookiePath = str_replace('autologin.php', 'index.php', $this->adminConfig->getCookiePath());
                $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()->setDuration(3600)
                                        ->setPath($cookiePath)->setDomain($this->adminConfig->getCookieDomain())
                                        ->setSecure($this->adminConfig->getCookieSecure())
                                        ->setHttpOnly($this->adminConfig->getCookieHttpOnly());
                $this->cookieManager->setPublicCookie($this->adminSession->getName(), $cookieValue, $cookieMetadata);
                $this->adminSessionManager->processLogin();
            }
            // get relayState URL and redirect the user to appropriate URL. Log the user to 
            // dashboard by default. 
        }
        $path = !$this->spUtility->isBlank($params['relaystate']) ? $params['relaystate'] 
                : $this->urlInterface->getStartupPageUrl();
        $url = $this->urlInterface->getUrl($path);
        $url = str_replace('autologin.php', 'index.php', $url);
        return $this->resultRedirectFactory->create()->setUrl($url);
    }
}