<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Certification;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Vantiv\Payment\Model\ResourceModel\Certification\Test\Result\CollectionFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\App\Action as BackendAction;
use Magento\Backend\Model\View\Result\Redirect;

class DownloadResults extends BackendAction
{
    /**
     * @var CollectionFactory
     */
    private $testCollectionFactory;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);

        $this->testCollectionFactory = $collectionFactory;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Run Vantiv Certification Tests
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $testCollection = $this->testCollectionFactory->create();

            return $this->fileFactory->create(
                $testCollection->getFileName(),
                $testCollection->export(),
                DirectoryList::VAR_DIR,
                $testCollection->getContentType()
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $this->messageManager->addErrorMessage(__('Please correct the data sent value.'));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');

        return $resultRedirect;
    }
}
