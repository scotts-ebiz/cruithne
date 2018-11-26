<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Info;

use Magento\Framework\App\Area;

/**
 * Class Info
 */
class Cc extends \Magento\Payment\Block\Info\Cc
{
    /**
     * Prepare CC payment information.
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if ($this->_paymentSpecificInformation === null) {
            $info = parent::_prepareSpecificInformation($transport);

            if ($this->isAdminArea()) {
                /*
                 * Add card product type data.
                 */
                $cardProductType = $this->getInfo()->getAdditionalInformation('card_product_type');
                if (!empty($cardProductType)) {
                    $info[(string) __('Card Product Type')] = $cardProductType;
                }

                /*
                 * Add issuer country data.
                 */
                $issuerCountry = $this->getInfo()->getAdditionalInformation('issuer_country');
                if (!empty($issuerCountry)) {
                    $info[(string) __('Issuer Country')] = $issuerCountry;
                }

                /*
                 * Add advanced fraud data.
                 */
                $deviceReviewStatus = $this->getInfo()->getAdditionalInformation('device_review_status');
                if (!empty($deviceReviewStatus)) {
                    $deviceReputationScore = $this->getInfo()->getAdditionalInformation('device_reputation_score');

                    $info[(string) __('Device Review Status')] = $deviceReviewStatus;
                    $info[(string) __('Device Reputation Score')] = $deviceReputationScore;
                }
            }

            $this->_paymentSpecificInformation = $info;
        }

        return $this->_paymentSpecificInformation;
    }

    /**
     * Check if we are in admin area.
     *
     * @return boolean
     */
    private function isAdminArea()
    {
        $adminAreaList = [
            Area::AREA_ADMIN,
            Area::AREA_ADMINHTML,
        ];

        $thisArea = $this->getArea();

        return in_array($thisArea, $adminAreaList);
    }
}
