<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\ResourceModel\Recurring;

class Subscription extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * Fields that should be serialized before persistence
     *
     * @var array
     */
    protected $_serializableFields = [
        'product_options' => [[], []],
        'payment_additional_information' => [[], []]
    ];

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->orderFactory = $orderFactory;
    }

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vantiv_subscriptions', 'subscription_id');
    }

    /**
     * Update subscription id in order table
     *
     * @param $orderId
     * @param $subscriptionId
     * @return $this
     */
    public function updateOrderRelation($orderId, $subscriptionId)
    {
        $orderResource = $this->orderFactory->create()->getResource();
        $orderResource->getConnection()->update(
            $orderResource->getMainTable(),
            ['vantiv_subscription_id' => $subscriptionId],
            ['entity_id = ?' => $orderId]
        );
        $orderResource->getConnection()->update(
            'sales_order_grid',
            ['vantiv_subscription_id' => $subscriptionId],
            ['entity_id = ?' => $orderId]
        );
        return $this;
    }

    /**
     * Create amount changelog record
     *
     * @param array $changeLogData
     * @return $this
     */
    public function addAmountChangelog($changeLogData)
    {
        $this->getConnection()->insert(
            $this->getTable('vantiv_subscription_amount_changelog'),
            $changeLogData
        );
        return $this;
    }

    /**
     * Retrieve subscription amount at given time
     *
     * @param \Vantiv\Payment\Model\Recurring\Subscription $object
     * @param \DateTime $dateTime
     * @return string|null
     */
    public function getTotalAmountToDate($object, \DateTime $dateTime)
    {
        if (!$object->getId()) {
            return null;
        }
        $dateTimeString = $dateTime->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $connection = $this->getConnection();
        $applicableLogIdsSelect = $connection->select()
            ->from(
                $this->getTable('vantiv_subscription_amount_changelog'),
                [new \Zend_Db_Expr('MAX(log_id)')]
            )->where('subscription_id = ?', $object->getId())
            ->where('updated_at <= ?', $dateTimeString)
            ->group(['entity_id', 'entity_type']);
        $select = $connection->select()
            ->from(
                $this->getTable('vantiv_subscription_amount_changelog'),
                [new \Zend_Db_Expr('SUM(amount)')]
            )
            ->where('log_id IN(?)', $applicableLogIdsSelect)
            ->where('start_date IS NULL ' . \Zend_Db_Select::SQL_OR . ' start_date <= ?', $dateTimeString)
            ->where('end_date IS NULL ' . \Zend_Db_Select::SQL_OR . ' end_date >= ?', $dateTimeString);

        return $connection->fetchOne($select);
    }
}
