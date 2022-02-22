<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Addon;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Locale\FormatInterface;
use Vantiv\Payment\Model\Recurring\Subscription\AddonFactory;

class Save extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Addon
{
    /**
     * @var FormatInterface
     */
    private $localeFormat;

    /**
     * @var AddonFactory
     */
    private $addonFactory;

    /**
     * @param Context $context
     * @param AddonFactory $addonFactory
     * @param FormatInterface $localeFormat
     */
    public function __construct(
        Context $context,
        AddonFactory $addonFactory,
        FormatInterface $localeFormat
    ) {
        parent::__construct($context);

        $this->localeFormat = $localeFormat;
        $this->addonFactory = $addonFactory;
    }

    /**
     * Execute save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();

        $resultRedirect->setPath('*/recurring_subscription/index');

        if ($data && isset($data['subscription_id']) && $data['subscription_id']) {
            $resultRedirect->setPath('*/recurring_subscription/view', ['subscription_id' => $data['subscription_id']]);
            if (array_key_exists('amount', $data) && !is_numeric($data['amount'])) {
                $data['amount'] = $this->localeFormat->getNumber($data['amount']);
            }

            if (isset($data['addon_id']) && !$data['addon_id']) {
                unset($data['addon_id']);
            }

            if (isset($data['start_date'])) {
                $startDate = new \DateTime($data['start_date']);
                $data['start_date'] = $startDate->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
            }

            if (isset($data['end_date'])) {
                $endDate = new \DateTime($data['end_date']);
                $data['end_date'] = $endDate->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
            }

            /** @var \Vantiv\Payment\Model\Recurring\Subscription\Addon $model */
            $model = $this->addonFactory->create();

            if (isset($data['addon_id'])) {
                $model->load($data['addon_id']);
                if (!$model->getId() || $model->getIsSystem()) {
                    $this->messageManager->addErrorMessage(__('Add-on no longer exists.'));
                    return $resultRedirect;
                }
                $data['code'] = $model->getCode();
            }

            $model->setData($data);

            try {
                $model->save();

                $this->messageManager->addSuccessMessage(__('The add-on has been saved.'));

                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager
                    ->addErrorMessage(__('An error occurred while saving the add-on.') . ' ' . $e->getMessage());

                return $resultRedirect;
            }
        }

        $this->messageManager->addErrorMessage(__('An error occurred while saving the add-on.'));

        return $resultRedirect;
    }
}
