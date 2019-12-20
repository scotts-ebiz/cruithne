<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\SubscriptionAccounts\Controller\Account;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory; 

class Index extends \Magento\Customer\Controller\AbstractAccount implements HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $_resultRedirect;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ResultFactory $resultFactory
    ) {
        $this->_resultRedirect = $resultFactory;
        parent::__construct($context);
    }

    /**
     * Redirect /customer/account to /account/settings
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultRedirect = $this->_resultRedirect->create( ResultFactory::TYPE_REDIRECT );
        $resultRedirect->setUrl('/account/settings');

        return $resultRedirect;
    }
}
