<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Recovery;

use Vantiv\Payment\Model\Recurring\Source\RecoveryTransactionStatus;

class Cancel extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Vantiv_Payment::recovery_transactions_actions_cancel';

    /**
     * @var \Vantiv\Payment\Model\Recurring\RecoveryTransactionFactory
     */
    private $recoveryTransactionFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Vantiv\Payment\Model\Recurring\RecoveryTransactionFactory $recoveryTransactionFactory ,
     * @param \Magento\Framework\Registry $coreRegistry ,
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Vantiv\Payment\Model\Recurring\RecoveryTransactionFactory $recoveryTransactionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->recoveryTransactionFactory = $recoveryTransactionFactory;
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Init recovery transaction model
     *
     * @return bool|\Vantiv\Payment\Model\Recurring\RecoveryTransaction
     */
    private function initRecoveryTransaction()
    {
        $id = $this->getRequest()->getParam('entity_id');
        if (!$id) {
            return false;
        }

        $recoveryTransaction = $this->recoveryTransactionFactory->create();
        $recoveryTransaction->load($id);

        if (!$recoveryTransaction->getId()) {
            return false;
        }

        $this->coreRegistry->register('current_recovery_transaction', $recoveryTransaction);

        return $recoveryTransaction;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $recoveryTransaction = $this->initRecoveryTransaction();
        if ($recoveryTransaction) {
            try {
                if ($recoveryTransaction->getStatus() == RecoveryTransactionStatus::DECLINED) {
                    $recoveryTransaction->setStatus(RecoveryTransactionStatus::CANCELLED)
                        ->save();
                    $this->messageManager->addSuccessMessage(__('You\'ve successfully canceled payment recovery.'));
                    return $resultRedirect->setPath('*/*/');
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find a payment recovery to cancel.'));
        return $resultRedirect->setPath('*/*/');
    }
}
