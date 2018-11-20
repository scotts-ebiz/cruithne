<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Recurring;

use Vantiv\Payment\Model\Recurring\Source\RecoveryTransactionStatus;

/**
 * Recovery transaction
 *
 * @method int getSubscriptionId()
 * @method RecoveryTransaction setSubscriptionId(int $value)
 * @method string getLitleTxnId()
 * @method RecoveryTransaction setLitleTxnId(string $value)
 * @method string getReportGroup()
 * @method RecoveryTransaction setReportGroup(string $value)
 * @method string getResponseCode()
 * @method RecoveryTransaction setResponseCode(string $value)
 * @method string getResponseMessage()
 * @method RecoveryTransaction setResponseMessage(string $value)
 * @method string getStatus()
 * @method RecoveryTransaction setStatus(string $value)
 */
class RecoveryTransaction extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Vantiv\Payment\Gateway\Recurring\VoidRecoveryTransactionCommand
     */
    private $voidRecoveryTransactionCommand;

    /**
     * RecoveryTransaction constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Vantiv\Payment\Gateway\Recurring\VoidRecoveryTransactionCommand $voidRecoveryTransactionCommand
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Vantiv\Payment\Gateway\Recurring\VoidRecoveryTransactionCommand $voidRecoveryTransactionCommand,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->voidRecoveryTransactionCommand = $voidRecoveryTransactionCommand;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vantiv\Payment\Model\ResourceModel\Recurring\RecoveryTransaction');
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        if (!$this->getSkipSendingToVantiv()) {
            if ($this->getId()
                && (!$this->getOrigData()
                    || $this->getOrigData('status') != $this->getData('status')
                    && $this->getStatus() == RecoveryTransactionStatus::CANCELLED)
            ) {
                $this->voidRecoveryTransactionCommand->execute(['recovery_transaction' => $this]);
            }
        }

        return parent::beforeSave();
    }
}
