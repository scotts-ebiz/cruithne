<?php
/**
 * @category    ClassyLlama
 * @copyright   Copyright (c) 2019 Classy Llama Studios, LLC
 */

namespace SMG\OfflineShipping\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use SMG\OfflineShipping\Model\Config\Source\Method;
use Zend\Serializer\Adapter\Json;

class Config extends AbstractHelper
{

    const CONFIG_PATH_CUSTOM_PRICES = 'carriers/flatrate/flat_rate_prices';

    /**
     * @var Method
     */
    protected $sourceMethod;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * Config constructor.
     *
     * @param Context $context
     * @param Json    $jsonSerializer
     */
    public function __construct(Context $context, Json $jsonSerializer, Method $sourceMethod)
    {
        parent::__construct($context);
        $this->jsonSerializer = $jsonSerializer;
        $this->sourceMethod = $sourceMethod;
    }

    /**
     * @return array|mixed
     */
    public function getFlatRatePrices()
    {
        $configValue = $this->scopeConfig->getValue(
            self::CONFIG_PATH_CUSTOM_PRICES,
            ScopeInterface::SCOPE_STORE
        );

        $unserializedValue = empty($configValue) ? [] : $this->jsonSerializer->unserialize($configValue);
        $returnValue = [];
        $availableMethods = $this->sourceMethod->getAvailableMethods();
        foreach ($unserializedValue as $code => $values) {
            // don't return any methods that aren't still in the source method
            if (\in_array($code, \array_keys($availableMethods))) {
                $values['rate'] = (float)$values['rate'];
                $returnValue[$code] = $values;
            }
        }

        return $returnValue;
    }
}
