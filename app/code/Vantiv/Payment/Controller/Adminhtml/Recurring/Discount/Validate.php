<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Discount;

class Validate extends \Vantiv\Payment\Controller\Adminhtml\Recurring\ValidationAbstract
{
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->getJsonResultFactory()->create();

        $response = $this->validate();

        $resultJson->setData($response);

        return $resultJson;
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        $messages = [];
        $messages += $this->validateStartEndDates();
        $messages += $this->validateAmount();

        return $this->generateResponse($messages);
    }
}
