<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Recovery;

use Vantiv\Payment\Model\Recurring\Source\RecoveryTransactionStatus;

class MassCancel extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Vantiv_Payment::recovery_transactions_actions_cancel';

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    private $filter;

    /**
     * @var \Vantiv\Payment\Model\ResourceModel\Recurring\RecoveryTransaction\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Vantiv\Payment\Model\ResourceModel\Recurring\RecoveryTransaction\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Vantiv\Payment\Model\ResourceModel\Recurring\RecoveryTransaction\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $cancelledNumber = 0;

        foreach ($collection as $recoveryTransaction) {
            if ($recoveryTransaction->getStatus() == RecoveryTransactionStatus::DECLINED) {
                try {
                    $recoveryTransaction->setStatus(RecoveryTransactionStatus::CANCELLED)
                        ->save();
                    $cancelledNumber++;
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(
                        __(
                            'Error cancelling payment recovery transaction with id %1: %2',
                            $recoveryTransaction->getLitleTxnId(),
                            $e->getMessage()
                        )
                    );
                }
            }
        }

        if ($cancelledNumber > 0) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 payment recoveries have been cancelled.', $cancelledNumber)
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
