<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

use Magento\Directory\Model\Config\Source\Country\Full as CountriesSourceModel;

/**
 * Suspect Issuer Country Source Model.
 */
class SuspectIssuerCountry implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var CountriesSourceModel
     */
    private $countriesSourceModel;

    /**
     * @param CountriesSourceModel $countriesSourceModel
     */
    public function __construct(CountriesSourceModel $countriesSourceModel)
    {
        $this->countriesSourceModel = $countriesSourceModel;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->countriesSourceModel->toOptionArray();
    }
}
