<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring;

use Magento\Backend\App\Action;

abstract class ValidationAbstract extends \Magento\Backend\App\Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Vantiv_Payment::subscriptions_actions_edit';

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Action\Context
     */
    private $context;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * ValidationAbstract constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->context = $context;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    abstract public function validate();

    /**
     * @return \Magento\Framework\Controller\Result\JsonFactory
     */
    public function getJsonResultFactory()
    {
        return $this->resultJsonFactory;
    }

    /**
     * @return array
     */
    public function validateStartEndDates()
    {
        $messages = [];

        $startDate = $this->context->getRequest()->getParam('start_date');
        $endDate = $this->context->getRequest()->getParam('end_date');

        $today = $this->timezone->date()->format('Y-m-d');
        $today = $this->dateTime->timestamp($today);

        $startDate = $this->dateTime->timestamp($startDate);
        $endDate = $this->dateTime->timestamp($endDate);

        if ($startDate < $today || $endDate < $today) {
            $messages[] = __('Dates must be set no earlier than today.');
        }

        if ($endDate < $startDate) {
            $messages[] = __('End Date must come after Start Date.');
        }

        return $messages;
    }

    /**
     * @return array
     */
    public function validateAmount()
    {
        $messages = [];

        $amount = $this->context->getRequest()->getParam('amount');
        if($amount > 9999999999.99) {
            $messages[] = __('Amount cannot be greater than 9999999999.99');
        }

        return $messages;
    }

    /**
     * @param array $messages
     * @return \Magento\Framework\DataObject
     */
    protected function generateResponse($messages)
    {
        $response = new \Magento\Framework\DataObject();

        if ($messages) {
            $response->setData('error', true);
            $response->setData('messages', $messages);
        } else {
            $response->setData('error', false);
        }

        return $response;
    }
}
