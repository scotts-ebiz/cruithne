<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Vault;

use Magento\Vault\Controller\CardsManagement;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Psr\Log\LoggerInterface as Logger;
use Vantiv\Payment\Helper\Vault as VaultHelper;
use Vantiv\Payment\Gateway\Cc\Config\VantivCcConfig as Config;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface as Payment;

class CcSave extends CardsManagement
{
    /**
     * Token manager.
     *
     * @var PaymentTokenManagementInterface
     */
    private $tokenManager = null;

    /**
     * Get token factory.
     *
     * @var PaymentTokenFactory
     */
    private $tokenFactory = null;

    /**
     * Form key validator.
     *
     * @var Validator
     */
    private $validator = null;

    /**
     * Gateway command manager.
     *
     * @var CommandManagerInterface
     */
    private $commandManager = null;

    /**
     * Vault helper
     *
     * @var VaultHelper
     */
    private $vaultHelper;

    /**
     * Logger
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Session $session
     * @param Validator $validator
     * @param PaymentTokenManagementInterface $tokenManager
     * @param PaymentTokenFactory $tokenFactory
     * @param CommandManagerInterface $commandManager
     * @param VaultHelper $vaultHelper
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Session $session,
        Validator $validator,
        PaymentTokenManagementInterface $tokenManager,
        PaymentTokenFactory $tokenFactory,
        CommandManagerInterface $commandManager,
        VaultHelper $vaultHelper,
        Logger $logger
    ) {
        parent::__construct($context, $session);

        $this->validator = $validator;
        $this->tokenManager = $tokenManager;
        $this->tokenFactory = $tokenFactory;
        $this->commandManager = $commandManager;
        $this->vaultHelper = $vaultHelper;
        $this->logger = $logger;
    }

    /**
     * Get token manager.
     *
     * @return PaymentTokenManagementInterface
     */
    private function getTokenManager()
    {
        return $this->tokenManager;
    }

    /**
     * Get token factory.
     *
     * @return PaymentTokenFactory
     */
    private function getTokenFactory()
    {
        return $this->tokenFactory;
    }

    /**
     * Get form key validator.
     *
     * @return Validator
     */
    private function getValidator()
    {
        return $this->validator;
    }

    /**
     * Get customer session.
     *
     * @return Session
     */
    private function getSession()
    {
        return $this->customerSession;
    }

    /**
     * Get session messages manager.
     *
     * @return \Magento\Framework\Message\ManagerInterface
     */
    private function getMessageManager()
    {
        return $this->messageManager;
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
     * Dispatch request
     *
     * @return Redirect
     */
    public function execute()
    {
        $request = $this->getRequest();
        $redirect = $this->getRedirectFactory()->create();
        $redirect->setPath('vault/cards/listaction');

        /*
         * Validate request.
         */
        if (!$this->getValidator()->validate($request) || !$this->getVaultHelper()->isCcVaultEnabled()) {
            $this->getMessageManager()->addErrorMessage('Wrong request.');
        } else {
            try {
                $publicHash = $request->getParam('public_hash');
                $customerId = $this->getSession()->getCustomerId();

                $token = $this->getTokenManager()->getByPublicHash($publicHash, $customerId);
                if ($token === null) {
                    /** @var PaymentTokenInterface $token */
                    $token = $this->getTokenFactory()->create(Payment::TOKEN_TYPE_CREDIT_CARD);
                }

                $token->setCustomerId($customerId);
                $token->setPaymentMethodCode(Config::METHOD_CODE);

                $command = $this->getCommandManager()->get('register_token');
                $command->execute([
                    'token' => $token,
                    'paypage_registration_id' => $request->getParam('paypage_registration_id'),
                    'last_four' => $request->getParam('last_four'),
                    'exp_month' => $request->getParam('exp_month'),
                    'exp_year' => $request->getParam('exp_year'),
                    'cc_type' => $request->getParam('cc_type'),
                ]);

                $this->getVaultHelper()->saveToken($token);

                $this->getMessageManager()
                    ->addSuccessMessage('Credit card was successfully saved.');
            } catch (CommandException $e) {
                $this->getMessageManager()
                    ->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->getMessageManager()
                    ->addErrorMessage('Credit card was not saved successfully. Please, try later.');
                $this->logger->error($e->getMessage());
            }
        }

        return $redirect;
    }

    /**
     * Get credit card command manager.
     *
     * @return CommandManagerInterface
     */
    private function getCommandManager()
    {
        return $this->commandManager;
    }

    /**
     * Get result redirect factory
     *
     * @return RedirectFactory
     */
    private function getRedirectFactory()
    {
        return $this->resultRedirectFactory;
    }
}
