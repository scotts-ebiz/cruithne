<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring;

use Magento\Framework\Exception\LocalizedException;

/**
 * Recurring Plan
 *
 * @method int getProductId()
 * @method \Vantiv\Payment\Model\Recurring\Plan setProductId(int $value)
 * @method int getWebsiteId()
 * @method \Vantiv\Payment\Model\Recurring\Plan setWebsiteId(int $value)
 * @method string getCode()
 * @method \Vantiv\Payment\Model\Recurring\Plan setCode(string $value)
 * @method string getName()
 * @method \Vantiv\Payment\Model\Recurring\Plan setName(string $value)
 * @method string getDescription()
 * @method \Vantiv\Payment\Model\Recurring\Plan setDescription(string $value)
 * @method int getNumberOfPayments()
 * @method \Vantiv\Payment\Model\Recurring\Plan setNumberOfPayments(int $value)
 * @method string getInterval()
 * @method \Vantiv\Payment\Model\Recurring\Plan setInterval(string $value)
 * @method float getIntervalAmount()
 * @method \Vantiv\Payment\Model\Recurring\Plan setIntervalAmount(float $value)
 * @method string getTrialInterval()
 * @method \Vantiv\Payment\Model\Recurring\Plan setTrialInterval(string $value)
 * @method int getNumberOfTrialIntervals()
 * @method \Vantiv\Payment\Model\Recurring\Plan setNumberOfTrialIntervals(int $value)
 * @method int getSortOrder()
 * @method \Vantiv\Payment\Model\Recurring\Plan setSortOrder(int $value)
 * @method int getActive()
 * @method \Vantiv\Payment\Model\Recurring\Plan setActive(int $value)
 * @method string getLitleTxnId()
 * @method \Vantiv\Payment\Model\Recurring\Plan setLitleTxnId(string $value)
 *
 */
class Plan extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Vantiv\Payment\Gateway\Recurring\CreatePlanCommand
     */
    private $createPlanCommand;

    /**
     * @var \Vantiv\Payment\Gateway\Recurring\UpdatePlanCommand
     */
    private $updatePlanCommand;

    /**
     * Plan constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Vantiv\Payment\Gateway\Recurring\CreatePlanCommand $createPlanCommand
     * @param \Vantiv\Payment\Gateway\Recurring\UpdatePlanCommand $updatePlanCommand
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Vantiv\Payment\Gateway\Recurring\CreatePlanCommand $createPlanCommand,
        \Vantiv\Payment\Gateway\Recurring\UpdatePlanCommand $updatePlanCommand,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->createPlanCommand = $createPlanCommand;
        $this->updatePlanCommand = $updatePlanCommand;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vantiv\Payment\Model\ResourceModel\Recurring\Plan');
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        if (!$this->getSkipSendingToVantiv()) {
            if (!$this->getId()) {
                try {
                    $this->createPlanCommand->execute(['plan' => $this]);
                } catch (\Exception $e) {
                    throw new LocalizedException(__('Error while saving plan in Vantiv: %1', $e->getMessage()));
                }
            } else {
                if (!$this->getOrigData() || $this->getOrigData('active') != $this->getData('active')) {
                    try {
                        $this->updatePlanCommand->execute(['plan' => $this]);
                    } catch (\Exception $e) {
                        throw new LocalizedException(__('Error while updating plan in Vantiv: %1', $e->getMessage()));
                    }
                }
            }
        }
        return parent::beforeSave();
    }
}
