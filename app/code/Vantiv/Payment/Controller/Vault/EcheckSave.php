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
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface as Logger;
use Vantiv\Payment\Model\Vault\EcheckTokenFactory;
use Vantiv\Payment\Gateway\Echeck\Config\VantivEcheckConfig as Config;
use Vantiv\Payment\Helper\Vault as VaultHelper;

class EcheckSave extends CardsManagement
{
    /**
     * Get token factory.
     *
     * @var EcheckTokenFactory
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
     * Json
     *
     * @var Json
     */
    private $json;

    /**
     * Logger
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $session
     * @param Validator $validator
     * @param EcheckTokenFactory $tokenFactory
     * @param CommandManagerInterface $commandManager
     * @param VaultHelper $vaultHelper
     * @param Json $json
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Session $session,
        Validator $validator,
        EcheckTokenFactory $tokenFactory,
        CommandManagerInterface $commandManager,
        VaultHelper $vaultHelper,
        Json $json,
        Logger $logger
    ) {
        parent::__construct($context, $session);

        $this->validator = $validator;
        $this->tokenFactory = $tokenFactory;
        $this->commandManager = $commandManager;
        $this->vaultHelper = $vaultHelper;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Get token factory.
     *
     * @return EcheckTokenFactory
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
     * Get Json instance
     *
     * @return Json
     */
    private function getJson()
    {
        return $this->json;
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
        if (!$this->getValidator()->validate($request) || !$this->getVaultHelper()->isEcheckVaultEnabled()) {
            $this->getMessageManager()->addErrorMessage('Wrong request.');
        } else {
            try {
                $customerId = $this->getSession()->getCustomerId();

                /** @var PaymentTokenInterface $token */
                $token = $this->getTokenFactory()->create(EcheckTokenFactory::TOKEN_TYPE_ECHECK);

                $details = [
                    'echeckAccountType' => $request->getParam('echeck_account_type'),
                    'maskedAccountNumber' => substr($request->getParam('echeck_account_name'), -3),
                    'echeckRoutingNumber' => $request->getParam('echeck_routing_number'),
                ];

                $token->setCustomerId($customerId);
                $token->setPaymentMethodCode(Config::METHOD_CODE);
                $token->setTokenDetails($this->getJson()->serialize($details));

                $command = $this->getCommandManager()->get('register_token');
                $command->execute([
                    'token' => $token,
                    'account_number' => $request->getParam('echeck_account_name'),
                    'routing_number' => $request->getParam('echeck_routing_number'),
                ]);

                $this->getVaultHelper()->saveToken($token);

                $this->getMessageManager()->addSuccessMessage('Account was successfully saved.');
            } catch (CommandException $e) {
                $this->getMessageManager()->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->getMessageManager()
                    ->addErrorMessage('Account was not saved successfully. Please, try later.');
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
