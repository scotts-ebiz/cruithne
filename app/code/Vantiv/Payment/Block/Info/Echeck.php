<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Info;

use Magento\Payment\Block\Info;

class Echeck extends Info
{
    /**
     * Prepare eCheck payment information.
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if ($this->_paymentSpecificInformation === null) {
            $info = parent::_prepareSpecificInformation($transport);

            $info[(string) __('Account Type')] = $this->getInfo()->getEcheckAccountType();
            $info[(string) __('Account Number')] = '******' . substr($this->getInfo()->getEcheckAccountName(), -3);
            $info[(string) __('Routing Number')] = $this->getInfo()->getEcheckRoutingNumber();

            $this->_paymentSpecificInformation = $info;
        }

        return $this->_paymentSpecificInformation;
    }
}
