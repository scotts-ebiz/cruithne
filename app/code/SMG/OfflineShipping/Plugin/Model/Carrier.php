<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\OfflineShipping\Plugin\Model;

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
     * @param SourceMethod $sourceMethod
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     */
    public function __construct(
        SourceMethod $sourceMethod,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory
    ) {
        $this->sourceMethod = $sourceMethod;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
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
        $allowedMethods = explode(",", $subject->getConfigData('allowed_methods'));
        $availableMethods = $this->sourceMethod->getAvailableMethods();
        $list = [];

        foreach ($availableMethods as $code => $title) {
            if (!in_array($code, $allowedMethods)) {
                continue;
            }

            $list[$code] = $title;
        }

        return $list;
    }

    /**
     * @param Flatrate $subject
     * @param $result
     * @return bool|Result
     */
    public function afterCollectRates(
        Flatrate $subject,
        $result
    ) {
        if (!$subject->getConfigFlag('active') || !$subject->getAllowedMethods()) {
            return false;
        }

        $price = $result->getCheapestRate()->getPrice();
        $cost = $result->getCheapestRate()->getCost();

        foreach ($subject->getAllowedMethods() as $code => $name) {
            /** @var Method $method */
            $method = $this->rateMethodFactory->create();
            $method->setCarrier(self::CODE);
            $carrierTitle = $subject->getConfigData('title');

            $method->setCarrierTitle($carrierTitle);
            $method->setMethod($code);
            $method->setMethodTitle($name);

            $method->setPrice($price);
            $method->setCost($cost);

            $result->append($method);
        }

        return $result;
    }
}
