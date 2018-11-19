<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\GiftCard;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action as BackendAction;
use Vantiv\Payment\Model\GiftCardAccount\ImportHandler;

class ImportCodes extends BackendAction
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ImportHandler
     */
    private $importHandler;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ImportHandler $importHandler
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ImportHandler $importHandler
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->importHandler = $importHandler;
    }

    /**
     * Import Gift Card Codes action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->getFiles('import_codes_file') !== null) {
            try {
                $this->importHandler->importFromTxtFile($this->getRequest()->getFiles('import_codes_file'));
                $this->messageManager->addSuccess(__('Gift Card Codes have been imported.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Gift Card Codes have not been imported.'));
            }
        } else {
            $this->messageManager->addError(__('Invalid file upload attempt. Please select file to import.'));
        }

        $response = ['data' => []];
        $resultJson = $this->jsonFactory->create();

        return $resultJson->setData($response);
    }
}
