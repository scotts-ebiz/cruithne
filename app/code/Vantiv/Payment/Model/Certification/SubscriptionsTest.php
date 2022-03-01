<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification;

use \Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Magento\Store\Model\StoreManagerInterface;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as VantivConfig;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;
use Vantiv\Payment\Gateway\Certification\TestCommand as Command;
use Vantiv\Payment\Gateway\Common\Builder\LitleOnlineRequestWrapper;

/**
 * Certification test model
 */
class SubscriptionsTest extends Subscriptions\AbstractSubscriptionsTest
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $testsToRun = [
        'Vantiv\Payment\Model\Certification\Subscriptions\CreateInitialPlansTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\SaleTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\AuthorizationTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\AuthorizationAdditionalServiceTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\UpdateSubscriptionTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\AuthorizationDuplicateAddOnTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\AuthorizationDuplicateDiscountTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\AuthorizationCcTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\UpdatePlanTest',
        'Vantiv\Payment\Model\Certification\Subscriptions\CancelSubscriptionTest',
    ];

    /**
     * Plan references
     *
     * @var array
     */
    private $planReferences = ['R1.1', 'R1.2', 'R1.3', 'R1.4', 'R1.5', 'R1.6'];

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Config $config,
        TestResponseParserFactory $parserFactory,
        StoreManagerInterface $storeManager,
        VantivConfig $vantivConfig,
        ResultFactory $resultFactory,
        ObjectManagerInterface $objectManager,
        Command $command,
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $parserFactory,
            $command,
            $resultFactory,
            $storeManager,
            $vantivConfig,
            $litleOnlineRequestWrapper,
            $data
        );
        $this->objectManager = $objectManager;
    }

    /**
     * Run Tests
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $planCodes = $this->generatePlanCodes();
        foreach ($this->testsToRun as $testModelName) {
            $testInstance = $this->objectManager->create($testModelName, ['data' => ['planCodes' => $planCodes]]);
            $testInstance->execute();
        }
    }

    private function generatePlanCodes()
    {
        $planCodes = [];
        foreach ($this->planReferences as $reference) {
            $planCodes[$reference] = uniqid();
        }

        return $planCodes;
    }
}
