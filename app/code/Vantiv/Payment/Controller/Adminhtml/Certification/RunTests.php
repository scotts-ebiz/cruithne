<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Adminhtml\Certification;

use Magento\Backend\App\Action\Context;
use Vantiv\Payment\Model\ResourceModel\Certification\Test\CollectionFactory;
use Vantiv\Payment\Model\Certification\TestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\App\Action as BackendAction;
use Magento\Framework\Controller\ResultInterface;

class RunTests extends BackendAction
{
    /**
     * @var CollectionFactory
     */
    private $testCollectionFactory;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param JsonFactory $jsonFactory
     */
    public function __construct(Context $context, CollectionFactory $collectionFactory, JsonFactory $jsonFactory)
    {
        parent::__construct($context);
        $this->testCollectionFactory = $collectionFactory;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Run Vantiv Certification Tests
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $selectedTestIds = $this->getRequest()->getPost('selectedTests');

            if (is_array($selectedTestIds)) {
                $selectedTests = $this->testCollectionFactory->create();
                $selectedTests->addFilter('id', $selectedTestIds, 'in');
                $selectedTests->loadData();
                /** @var $test TestInterface */
                foreach ($selectedTests as $test) {
                    $test->execute();
                }
            }
            $this->messageManager->addSuccessMessage(__('Certification Tests have been completed.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Certification Tests has not been completed.'));
        }

        $response = ['data' => []];
        $resultJson = $this->jsonFactory->create();

        return $resultJson->setData($response);
    }
}
