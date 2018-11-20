<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Ui\Component\Form;

class Addon extends \Magento\Ui\Component\Form
{
    /**
     * Populate subscription_id from request params
     *
     * @return array
     */
    public function getDataSourceData()
    {
        $dataSource = parent::getDataSourceData();

        if ($subscriptionId = $this->context->getRequestParam('subscription_id')) {
            $dataSource['data']['subscription_id'] = $subscriptionId;
        }

        return $dataSource;
    }
}
