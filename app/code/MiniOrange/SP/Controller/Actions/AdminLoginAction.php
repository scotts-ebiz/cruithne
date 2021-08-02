<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\Session\AdminConfig;
use Magento\Backend\Model\Url;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\User\Model\UserFactory;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPUtility;
use Psr\Log\LoggerInterface;

/**
 * This class is called from the observer class to log the
 * admin user in. Read the appropriate values required from the
 * requset parameter passed along with the redirect to log the user in.
 * <b>NOTE</b> : Admin ID, Session Index and relaystate are passed
 *              in the request parameter.
 */
class AdminLoginAction extends BaseAdminAction
{
    private $relayState;
    private $user;
    private $adminSession;
    private $cookieManager;
    private $adminConfig;
    private $cookieMetadataFactory;
    private $urlInterface;
    private $userFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SPUtility $spUtility,
        LoggerInterface $logger,
        Session $adminSession,
        CookieManagerInterface $cookieManager,
        AdminConfig $adminConfig,
        CookieMetadataFactory $cookieMetadataFactory,
        Url $urlInterface,
        UserFactory $userFactory
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->adminSession = $adminSession;
        $this->cookieManager = $cookieManager;
        $this->adminConfig =$adminConfig;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->urlInterface = $urlInterface;
        $this->userFactory = $userFactory;
        parent::__construct($context, $resultPageFactory, $spUtility, $logger);
    }

    /**
     * Execute function to execute the classes function.
     */
    public function execute()
    {
        /**
         * Check if valid request by checking the SESSION_INDEX in the request
         * and the session index in the database. If they don't match then return
         * This is done to take care of the backdoor that this URL creates if no
         * session index is checked
         */
        $params = $this->REQUEST; // get request params
        $sessionIndex = $this->spUtility->getAdminStoreConfig(SPConstants::SESSION_INDEX, $params['userid']);
        $sessionIndexInRequest = $params['sessionindex'];
        if (strcasecmp($sessionIndex, $sessionIndexInRequest)!=0) {
            return;
        }
        $user = $this->userFactory->create()->load($params['userid']);
        $this->adminSession->setUser($user);
        $this->adminSession->processLogin();
        if (SPUtility::getMagnetoVersion()>2.0) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $sessionManager = $objectManager->get('Magento\Security\Model\AdminSessionsManager');
            $sessionManager->processLogin();
        }
        $path = !$this->spUtility->isBlank($params['relaystate']) && $params['relaystate']!="/"
                ? $params['relaystate'] : $this->urlInterface->getStartupPageUrl();
        $url = $this->urlInterface->getUrl($path);
        $url = str_replace('autologin.php', 'index.php', $url);
        return $url;
    }
}
