<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\GiftCardAccount;

use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\ReadFactory;

/**
 * Gift Card Codes Import Handler
 */
class ImportHandler
{
    /**
     * Gift Card Code Length
     *
     * @var int
     */
    const GC_CODE_LENGTH = 19;

    /**
     * @var ReadFactory
     */
    private $readFactory;

    /**
     * @var PoolFactory
     */
    private $giftCardAccountPoolFactory;

    /**
     * Constructor
     *
     * @param ReadFactory $readFactory
     * @param PoolFactory $giftCardAccountPoolFactory
     */
    public function __construct(
        ReadFactory $readFactory,
        PoolFactory $giftCardAccountPoolFactory
    ) {
        $this->readFactory = $readFactory;
        $this->giftCardAccountPoolFactory = $giftCardAccountPoolFactory;
    }

    /**
     * Import Gift Card Codes from TXT file
     *
     * @param array $file file info retrieved from $_FILES array
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromTxtFile($file)
    {
        if (!isset($file['tmp_name'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
        }

        $codesRawData = $this->parseTxtFile($file['tmp_name']);
        $giftCardAccountPool = $this->giftCardAccountPoolFactory->create();
        $giftCardAccountPool->cleanupFree();

        foreach ($codesRawData as $rowIndex => $dataRow) {
            // skip headers
            if ($rowIndex == 0) {
                continue;
            }
            $codeRow = preg_split("/[\s,]+/", $dataRow);
            $code = array_key_exists(2, $codeRow) ? substr($codeRow[2], 0, self::GC_CODE_LENGTH) : false;
            if ($code) {
                $giftCardAccountPool->getResource()->saveCode($code);
            }
        }
    }

    /**
     * Retrieve TXT file data as array
     *
     * @param   string $file
     * @return  array
     * @throws \Exception
     */
    public function parseTxtFile($file)
    {
        if (!file_exists($file)) {
            throw new \Exception('File "' . $file . '" does not exist');
        }

        /** @var $fileReader \Magento\Framework\Filesystem\File\Read   */
        $fileReader = $this->readFactory->create($file, DriverPool::FILE);
        $fileStr = $fileReader->readAll($file);
        $data = explode("\n", $fileStr);

        return $data;
    }
}
