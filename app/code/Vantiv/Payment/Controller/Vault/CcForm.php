<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Vault;

use Magento\Vault\Controller\CardsManagement;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Vantiv\Payment\Helper\Vault as VaultHelper;

class CcForm extends CardsManagement
{
    /**
     * Page factory.
     *
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * Vault helper
     *
     * @var VaultHelper
     */
    private $vaultHelper;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Session $session
     * @param PageFactory $pageFactory
     * @param VaultHelper $vaultHelper
     */
    public function __construct(
        Context $context,
        Session $session,
        PageFactory $pageFactory,
        VaultHelper $vaultHelper
    ) {
        parent::__construct($context, $session);

        $this->pageFactory = $pageFactory;
        $this->vaultHelper = $vaultHelper;
    }

    /**
     * Get page factory.
     *
     * @return PageFactory
     */
    private function getPageFactory()
    {
        return $this->pageFactory;
    }

    /**
     * Get redirect factory
     *
     * @return RedirectFactory
     */
    private function getRedirectFactory()
    {
        return $this->resultRedirectFactory;
    }

    /**
     * Get Vault helper instance
     *
     * @return VaultHelper
     */
    private function getVaultHelper()
    {
        return $this->vaultHelper;
    }

    /**
     * Dispatch request.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if (!$this->getVaultHelper()->isCcVaultEnabled()) {
            $redirect = $this->getRedirectFactory()->create();
            $redirect->setPath('vault/cards/listaction');
            return $redirect;
        }

        $page = $this->getPageFactory()->create();
        $page->getConfig()->getTitle()->set(__('Stored Payment Methods'));

        $navigation = $page->getLayout()->getBlock('customer_account_navigation');
        if ($navigation) {
            $navigation->setActive('vault/cards/listaction');
        }

        return $page;
    }
}
