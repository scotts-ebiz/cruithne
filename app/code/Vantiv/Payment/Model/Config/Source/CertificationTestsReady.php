<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

use Vantiv\Payment\Model\ResourceModel\Certification\Test\CollectionFactory;
use Vantiv\Payment\Model\Config\Source\VantivEnvironment;

/**
 * List of Certification Tests ready to Run
 */
class CertificationTestsReady implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var CollectionFactory
     */
    private $testCollectionFactory;

    /**
     * @param CollectionFactory $testCollectionFactory
     */
    public function __construct(CollectionFactory $testCollectionFactory)
    {
        $this->testCollectionFactory = $testCollectionFactory;
    }

    /**
     * Array of Vantiv features available for testing
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->testCollectionFactory
                ->create()
                ->addFilter('active', 1)
                ->addFilter('environment', VantivEnvironment::PRELIVE)
                ->loadData()
                ->toOptionArray();
        }

        return $this->options;
    }
}
