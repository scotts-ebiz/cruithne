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
     * @var \SMG\OfflineShipping\Helper\Config
     */
    protected $config;

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
        ScopeConfigInterface $scopeConfig,
        \SMG\OfflineShipping\Helper\Config $config
    ) {
        $this->sourceMethod = $sourceMethod;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
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

        foreach ($availableMethods as $code => $title) {
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
        $allMethods = $this->sourceMethod->getAvailableMethods();

        /** @var \SMG\OfflineShipping\Model\ShippingConditionCode $method */
        foreach ($this->config->getFlatRatePrices() as $code => $rateInfo) {
            if (\in_array($code, $allowedMethods)) {
                /** @var Method $method */
                $method = $this->rateMethodFactory->create();
                $method->setCarrier(self::CODE);
                $carrierTitle = $subject->getConfigData('title');
                $method->setCarrierTitle($carrierTitle);
                $method->setMethod($code);
                $method->setMethodTitle($allMethods[$code]);
                $method->setPrice($rateInfo['rate']);
                $method->setCost($rateInfo['rate']);
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
