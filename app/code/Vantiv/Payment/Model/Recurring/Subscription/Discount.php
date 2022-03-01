<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Subscription;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Vantiv\Payment\Gateway\Recurring\CreateDiscountSubscriptionCommand;
use Vantiv\Payment\Gateway\Recurring\DeleteDiscountSubscriptionCommand;
use Vantiv\Payment\Gateway\Recurring\UpdateDiscountSubscriptionCommand;
use Vantiv\Payment\Model\Recurring\Subscription;
use Vantiv\Payment\Model\Recurring\Subscription\Discount\Validator;
use Vantiv\Payment\Model\Recurring\SubscriptionFactory;
use Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Discount as DiscountModel;
use Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Discount\Collection as DiscountCollection;

/**
 * Subscription Discount
 *
 * @method string getSubscriptionId()
 * @method Discount setSubscriptionId(string $value)
 * @method string getCode()
 * @method Discount setCode(string $value)
 * @method string getName()
 * @method Discount setName(string $value)
 * @method float getAmount()
 * @method Discount setAmount(float $value)
 * @method string getStartDate()
 * @method Discount setStartDate(string $value)
 * @method string getEndDate()
 * @method Discount setEndDate(string $value)
 *
 */
class Discount extends \Magento\Framework\Model\AbstractModel
{
    const REGISTRY_NAME = 'current_vantiv_discount';

    const DISCOUNT_CODE = 'discount';
    const DISCOUNT_NAME = 'Discount';
    const RECONCILIATION_CODE = 'reconciliation';

    /**
     * @var CreateDiscountSubscriptionCommand
     */
    private $createDiscountSubscriptionCommand;
    /**
     * @var UpdateDiscountSubscriptionCommand
     */
    private $updateDiscountSubscriptionCommand;
    /**
     * @var DeleteDiscountSubscriptionCommand
     */
    private $deleteDiscountSubscriptionCommand;
    /**
     * @var Discount\Validator
     */
    private $validator;
    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;
    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * Discount constructor.
     * @param Context $context
     * @param Registry $registry
     * @param DiscountModel|NULL $resource
     * @param DiscountCollection|NULL $resourceCollection
     * @param CreateDiscountSubscriptionCommand $createDiscountSubscriptionCommand
     * @param UpdateDiscountSubscriptionCommand $updateDiscountSubscriptionCommand
     * @param DeleteDiscountSubscriptionCommand $deleteDiscountSubscriptionCommand
     * @param Validator $validator
     * @param SubscriptionFactory $subscriptionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DiscountModel $resource = null,
        DiscountCollection $resourceCollection = null,
        CreateDiscountSubscriptionCommand $createDiscountSubscriptionCommand,
        UpdateDiscountSubscriptionCommand $updateDiscountSubscriptionCommand,
        DeleteDiscountSubscriptionCommand $deleteDiscountSubscriptionCommand,
        Validator $validator,
        SubscriptionFactory $subscriptionFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->createDiscountSubscriptionCommand = $createDiscountSubscriptionCommand;
        $this->updateDiscountSubscriptionCommand = $updateDiscountSubscriptionCommand;
        $this->deleteDiscountSubscriptionCommand = $deleteDiscountSubscriptionCommand;
        $this->validator = $validator;
        $this->subscriptionFactory = $subscriptionFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Discount');
    }

    /**
     * @inheritdoc
     */
    protected function _getValidationRulesBeforeSave()
    {
        return $this->validator;
    }

    /**
     * Dispatch command to create or update Discount
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function beforeSave()
    {
        if (!$this->getSkipSendingToVantiv()) {
            if (!$this->getId() && !$this->getOrigData()) {
                $this->createDiscountSubscriptionCommand->execute(['discount' => $this]);
            } else {
                $this->updateDiscountSubscriptionCommand->execute(['discount' => $this]);
            }
        }

        return parent::beforeSave();
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave()
    {
        if (!$this->getOrigData() || $this->dataHasChangedFor('amount')
            || $this->dataHasChangedFor('start_date') || $this->dataHasChangedFor('end_date')
        ) {
            $subscriptionModel = $this->subscriptionFactory->create();
            $changeLogData = [
                'subscription_id' => $this->getSubscriptionId(),
                'entity_id' => $this->getId(),
                'entity_type' => 'discount',
                'amount' => -$this->getAmount()
            ];
            if ($this->getStartDate()) {
                $changeLogData['start_date'] = $this->getStartDate() . ' 00:00:00';
            }
            if ($this->getEndDate()) {
                $changeLogData['end_date'] = $this->getEndDate() . ' 23:59:59';
            }
            $subscriptionModel->addAmountChangelog($changeLogData);
        }

        return parent::afterSave();
    }

    /**
     * Dispatch command to delete Discount
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function beforeDelete()
    {
        if (!$this->getSkipSendingToVantiv()) {
            if ($this->getId() && $this->getCode()) {
                $this->deleteDiscountSubscriptionCommand->execute(['discount' => $this]);
            }
        }
        return parent::beforeDelete();
    }

    /**
     * {@inheritdoc}
     */
    public function afterDelete()
    {
        $subscriptionModel = $this->subscriptionFactory->create();
        $subscriptionModel->addAmountChangelog([
            'subscription_id' => $this->getSubscriptionId(),
            'entity_id' => $this->getId(),
            'entity_type' => 'discount',
            'amount' => 0
        ]);
        return parent::afterDelete();
    }

    /**
     * Set subscription
     *
     * @param Subscription $subscription
     * @return $this
     */
    public function setSubscription(Subscription $subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * Retrieve associated Subscription model
     *
     * @return Subscription
     */
    public function getSubscription()
    {
        if (!$this->subscription) {
            $subscription = $this->subscriptionFactory->create();
            $subscription = $subscription->load($this->getSubscriptionId());

            $this->subscription = $subscription;
        }

        return $this->subscription;
    }
}
