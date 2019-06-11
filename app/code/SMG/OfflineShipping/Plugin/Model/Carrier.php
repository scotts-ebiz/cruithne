<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\OfflineShipping\Plugin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\OfflineShipping\Model\Carrier\Flatrate;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use SMG\OfflineShipping\Model\Config\Source\Method as SourceMethod;

/**
 * Class Carrier
 * @package SMG\OfflineShipping\Plugin\Model
 */
class Carrier
{
    /**
     * @var string
     */
    const CODE = 'flatrate';

    /**
     * @var string
     */
    const DEFAULT_TITLE = 'Flat Rate';

    /**
     * @var SourceMethod
     */
    private $sourceMethod;

    /**
     * @var ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param SourceMethod         $sourceMethod
     * @param ResultFactory        $rateResultFactory
     * @param MethodFactory        $rateMethodFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SourceMethod $sourceMethod,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->sourceMethod = $sourceMethod;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Flatrate $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllowedMethods(
        Flatrate $subject,
        array $result
    ) {
        $availableMethods = $this->sourceMethod->getAvailableMethods();
        $allowedCodes = $this->getConfiguredAllowedMethods();
        $list = [];

        foreach ($availableMethods as $method) {
            $code = $method->getShippingMethod();
            $title = $method->getDescription();
            if (\in_array($code, $allowedCodes)) {
                $list[$code] = $title;
            }
        }

        return $list;
    }
    /**
     * @param Flatrate $subject
     * @param \Magento\Shipping\Model\Rate\Result $result
     * @return bool|Result
     */
    public function afterCollectRates(
        Flatrate $subject,
        $result
    ) {

        if (!$subject->getConfigFlag('active') || !$subject->getAllowedMethods()) {
            return false;
        }

        $allowedMethods = $this->getConfiguredAllowedMethods();

        /** @var \SMG\OfflineShipping\Model\ShippingConditionCode $method */
        foreach ($this->sourceMethod->getAvailableMethods() as $conditionCode) {
            if (\in_array($conditionCode->getShippingMethod(), $allowedMethods)) {
                /** @var Method $method */
                $method = $this->rateMethodFactory->create();
                $method->setCarrier(self::CODE);
                $carrierTitle = $subject->getConfigData('title');
                $method->setCarrierTitle($carrierTitle);
                $method->setMethod($conditionCode->getShippingMethod());
                $method->setMethodTitle($conditionCode->getDescription());
                $method->setPrice($conditionCode->getRate());
                $method->setCost($conditionCode->getRate());
                $result->append($method);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getConfiguredAllowedMethods()
    {
        $allowedMethods = $this->scopeConfig->getValue(
            'carriers/flatrate/allowed_methods',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return empty($allowedMethods) ? [] : explode(',', $allowedMethods);
    }
}
