<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Subscription;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Vantiv\Payment\Gateway\Recurring\CreateAddonSubscriptionCommand;
use Vantiv\Payment\Gateway\Recurring\UpdateAddonSubscriptionCommand;
use Vantiv\Payment\Gateway\Recurring\DeleteAddonSubscriptionCommand;
use Vantiv\Payment\Model\Recurring\Subscription;
use Vantiv\Payment\Model\Recurring\SubscriptionFactory;
use Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon as AddonResourceModel;
use Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon\Collection as AddonCollection;

/**
 * Subscription Addon
 *
 * @method string getSubscriptionId()
 * @method Addon setSubscriptionId(string $value)
 * @method string getCode()
 * @method Addon setCode(string $value)
 * @method string getName()
 * @method Addon setName(string $value)
 * @method float getAmount()
 * @method Addon setAmount(float $value)
 * @method string getStartDate()
 * @method Addon setStartDate(string $value)
 * @method string getEndDate()
 * @method Addon setEndDate(string $value)
 *
 */
class Addon extends AbstractModel
{
    const REGISTRY_NAME = 'current_vantiv_addon';

    const TAX_CODE = 'tax';
    const TAX_NAME = 'Tax';

    const SHIPPING_CODE = 'shipping';
    const SHIPPING_NAME = 'Shipping';

    const RECONCILIATION_CODE = 'reconciliation';

    /**
     * @var CreateAddonSubscriptionCommand
     */
    private $createAddonSubscriptionCommand;
    /**
     * @var UpdateAddonSubscriptionCommand
     */
    private $updateAddonSubscriptionCommand;
    /**
     * @var DeleteAddonSubscriptionCommand
     */
    private $deleteAddonSubscriptionCommand;
    /**
     * @var Addon\Validator
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
     * Addon constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param AddonResourceModel|NULL $resource
     * @param AddonCollection|NULL $resourceCollection
     * @param CreateAddonSubscriptionCommand $createAddonSubscriptionCommand
     * @param UpdateAddonSubscriptionCommand $updateAddonSubscriptionCommand
     * @param DeleteAddonSubscriptionCommand $deleteAddonSubscriptionCommand
     * @param Addon\Validator $validator
     * @param SubscriptionFactory $subscriptionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AddonResourceModel $resource = null,
        AddonCollection $resourceCollection = null,
        CreateAddonSubscriptionCommand $createAddonSubscriptionCommand,
        UpdateAddonSubscriptionCommand $updateAddonSubscriptionCommand,
        DeleteAddonSubscriptionCommand $deleteAddonSubscriptionCommand,
        Addon\Validator $validator,
        SubscriptionFactory $subscriptionFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->createAddonSubscriptionCommand = $createAddonSubscriptionCommand;
        $this->updateAddonSubscriptionCommand = $updateAddonSubscriptionCommand;
        $this->deleteAddonSubscriptionCommand = $deleteAddonSubscriptionCommand;
        $this->validator = $validator;
        $this->subscriptionFactory = $subscriptionFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vantiv\Payment\Model\ResourceModel\Recurring\Subscription\Addon');
    }

    /**
     * {@inheritdoc}
     */
    protected function _getValidationRulesBeforeSave()
    {
        return $this->validator;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave()
    {
        if (!$this->getSkipSendingToVantiv()) {
            if (!$this->getId() && !$this->getOrigData()) {
                $this->createAddonSubscriptionCommand->execute(['addon' => $this]);
            } else {
                $this->updateAddonSubscriptionCommand->execute(['addon' => $this]);
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
                'entity_type' => 'addon',
                'amount' => $this->getAmount()
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
     * Dispatch command to delete Add-On
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function beforeDelete()
    {
        if (!$this->getSkipSendingToVantiv()) {
            if ($this->getId() && $this->getCode()) {
                $this->deleteAddonSubscriptionCommand->execute(['addon' => $this]);
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
            'entity_type' => 'addon',
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
