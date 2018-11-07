<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\ResourceModel\Certification\Test;

use Vantiv\Payment\Model\Certification\TestInterface;

/**
 * Certification test collection
 */
class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * Array of features to test
     *
     * @var array
     */
    private $testModelClasses = [
        'Vantiv\Payment\Model\Certification\CreditCardTest',
        'Vantiv\Payment\Model\Certification\EcheckTest',
        'Vantiv\Payment\Model\Certification\GiftCardTest',
        'Vantiv\Payment\Model\Certification\AdvancedFraudTest',
        'Vantiv\Payment\Model\Certification\AndroidpayTest',
        'Vantiv\Payment\Model\Certification\ApplepayTest',
        'Vantiv\Payment\Model\Certification\PayPalTest',
        'Vantiv\Payment\Model\Certification\Token\CcTokenTest',
        'Vantiv\Payment\Model\Certification\Token\EcheckTokenTest',
        'Vantiv\Payment\Model\Certification\SubscriptionsTest',
    ];

    /**
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if (!$this->isLoaded()) {
            foreach ($this->testModelClasses as $testModelClass) {
                $item = $this->_entityFactory->create($testModelClass);
                $item->setId($item->getId());
                $item->setName($item->getName());
                $this->addItem($item);
            }
            $this->_setIsLoaded(true);
            $this->_renderFilters();
        }

        return $this;
    }

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        return $this->loadData($printQuery, $logQuery);
    }

    /**
     * Apply set field filters
     *
     * @return $this
     */
    protected function _renderFilters()
    {
        $filters = $this->getFilter([]);
        /** @var $test TestInterface */
        foreach ($this->getItems() as $itemKey => $test) {
            foreach ($filters as $filter) {
                $fieldToCompare = $test->getDataUsingMethod($filter['field']);
                if (($filter['type'] == 'and' && $fieldToCompare != $filter['value'])
                    || ($filter['type'] == 'in' && !in_array($fieldToCompare, $filter['value']))) {
                    $this->removeItemByKey($itemKey);
                    break;
                }
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('id', 'name');
    }
}
