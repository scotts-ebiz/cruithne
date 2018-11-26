<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Cron;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Filesystem\DirectoryList;

class ImportBatchResponse
{
    /**
     * Number of days to look back for import files
     */
    const MAX_DAYS_IN_PAST_TO_PROCESS = 50;

    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    private $sftpClient;

    /**
     * @var \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig
     */
    private $config;

    /**
     * @var \Vantiv\Payment\Model\Import\ProcessingDateFactory
     */
    private $importProcessingDateFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $tmpDirectory;

    /**
     * @var \Vantiv\Payment\Model\BatchResponse\Item\AbstractHandler
     */
    private $handler;

    /**
     * Import code, used for tmp file name generation and for config path where last imported date is stored
     *
     * @var string
     */
    private $importCode;

    /**
     * Import title
     *
     * @var string
     */
    private $importTitle;

    /**
     * Import file name pattern
     *
     * @var string
     */
    private $filePattern;

    /**
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftpClient
     * @param \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $config
     * @param \Vantiv\Payment\Model\Import\ProcessingDateFactory $importProcessingDateFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Vantiv\Payment\Model\BatchResponse\Item\AbstractHandler $handler
     * @param string $importCode
     * @param string $importTitle
     * @param string $filePattern
     */
    public function __construct(
        \Magento\Framework\Filesystem\Io\Sftp $sftpClient,
        \Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig $config,
        \Vantiv\Payment\Model\Import\ProcessingDateFactory $importProcessingDateFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Vantiv\Payment\Model\BatchResponse\Item\AbstractHandler $handler,
        $importCode,
        $importTitle,
        $filePattern
    ) {
        $this->sftpClient = $sftpClient;
        $this->config = $config;
        $this->importProcessingDateFactory = $importProcessingDateFactory;
        $this->storeManager = $storeManager;
        $this->tmpDirectory = $filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $this->handler = $handler;
        $this->importCode = $importCode;
        $this->importTitle = $importTitle;
        $this->filePattern = $filePattern;
    }

    /**
     * Import data
     */
    public function import()
    {
        if (!$this->tmpDirectory->isWritable()) {
            $this->logError('temporary directory is not writable');
            return;
        }

        $defaultMerchantId = $this->config->getValue('merchant_id');
        foreach ($this->storeManager->getWebsites(true) as $website) {
            $merchantId = $this->config->getValue('merchant_id', $website->getId(), ScopeInterface::SCOPE_WEBSITE);
            if (!$this->config->getValue('active', $website->getId(), ScopeInterface::SCOPE_WEBSITE)
                || ($merchantId == $defaultMerchantId && $website->getId() != 0)
            ) {
                continue;
            }

            $this->handler->setWebsiteId($website->getId());

            $sftpLogin = $this->config->getValue('sftp_login', $website->getId(), ScopeInterface::SCOPE_WEBSITE);
            $sftpPassword = $this->config->getValue('sftp_password', $website->getId(), ScopeInterface::SCOPE_WEBSITE);
            $sftpHost = $this->config->getValue('sftp_host', $website->getId(), ScopeInterface::SCOPE_WEBSITE);
            if (!$sftpLogin || !$sftpPassword || !$sftpHost) {
                $this->logError('SFTP credentials not fully configured, website id: ' . $website->getId());
                continue;
            }

            try {
                $this->sftpClient->open([
                    'host' => $sftpHost,
                    'username' => $sftpLogin,
                    'password' => $sftpPassword
                ]);
                $sftpPath = $this->config->getValue('sftp_path', $website->getId(), ScopeInterface::SCOPE_WEBSITE);
                if ($sftpPath) {
                    if (!$this->sftpClient->cd($sftpPath)) {
                        $this->logError(
                            'unable to change SFTP directory, website id: ' . $website->getId()
                        );
                        continue;
                    }
                }

                $filesNames = $this->filterResponseFiles($this->sftpClient->rawls(), $merchantId);
            } catch (\Exception $e) {
                $this->logError(
                    'unable to read the files on SFTP, website id: '
                    . $website->getId() . ', exception message: ' . $e->getMessage()
                );
                continue;
            }

            foreach ($filesNames as $fileName) {
                $tmpFile = 'Vantiv_' . $this->importCode . '_'
                    . uniqid(\Magento\Framework\Math\Random::getRandomNumber()) . time() . '.xml';
                if (!$this->sftpClient->read($fileName, $this->tmpDirectory->getAbsolutePath($tmpFile))) {
                    $this->logError('cannot read the file on SFTP: ' . $fileName);
                    continue;
                }

                try {
                    $fileContents = $this->tmpDirectory->readFile($tmpFile);
                } catch (\Exception $e) {
                    $this->logError(
                        'cannot read local import file, tmp file name: ' . $tmpFile
                        . ', SFTP import file name ' . $fileName
                    );
                    continue;
                }

                $libxmlUseInternalErrors = libxml_use_internal_errors(true);
                $xml = simplexml_load_string($fileContents);
                libxml_use_internal_errors($libxmlUseInternalErrors);
                if ($xml === false) {
                    $errorMessage = 'errors while parsing ' . $fileName . ':';
                    foreach (libxml_get_errors() as $xmlError) {
                        $errorMessage .= "\n" . $xmlError->message;
                    }
                    $this->logError($errorMessage);
                    libxml_clear_errors();
                    continue;
                }

                if (!isset($xml->batchResponse)) {
                    $this->logError('error while parsing ' . $fileName . ' - batchResponse node not found');
                    continue;
                }

                foreach ($xml->batchResponse->children() as $saleResponse) {
                    $this->handler->handle($saleResponse);
                }

                $this->tmpDirectory->delete($tmpFile);
            }
        }
    }

    /**
     * Log error message
     *
     * @param string $message
     * @return $this
     */
    private function logError($message)
    {
        $this->handler->logError($this->importTitle . ' import failed: ' . $message);
        return $this;
    }

    /**
     * Filter files on SFTP
     *
     * @param array $files
     * @param string $merchantId
     * @return array
     */
    private function filterResponseFiles(array $files, $merchantId)
    {
        $matchingFileNames = [];
        if (!$files) {
            return $matchingFileNames;
        }

        $lastProcessedDate = null;
        $importProcessingDate = $this->importProcessingDateFactory->create()
            ->loadByImportCodeAndMerchantId($this->importCode, $merchantId);
        if ($importProcessingDate->getId()) {
            $lastProcessedDate = $importProcessingDate->getLastProcessedDate();
        } else {
            $importProcessingDate->setImportCode($this->importCode)
                ->setMerchantId($merchantId);
        }

        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        if ($lastProcessedDate) {
            $lastProcessedDate = new \DateTime($lastProcessedDate);
            $diff = $today->diff($lastProcessedDate, true);
            if ($diff->days > self::MAX_DAYS_IN_PAST_TO_PROCESS) {
                $lastProcessedDate = clone $today;
                $lastProcessedDate->sub(
                    new \DateInterval('P' . (self::MAX_DAYS_IN_PAST_TO_PROCESS + 1) . 'D')
                );
            }
        }

        if (!$lastProcessedDate) {
            $lastProcessedDate = new \DateTime();
            $lastProcessedDate->setTime(0, 0, 0);
            $lastProcessedDate->sub(new \DateInterval('P2D'));
        }

        $lastProcessedDate->add(new \DateInterval('P1D'));
        while ($lastProcessedDate < $today) {
            // check if file matching pattern
            $patternVars = [
                '{$merchantId}' => preg_quote($merchantId),
                '{$mdY}' => $lastProcessedDate->format('mdY')
            ];
            $pattern = '/^' . strtr($this->filePattern, $patternVars) . '$/i';
            foreach ($files as $fileInfo) {
                if (preg_match($pattern, $fileInfo['filename'])) {
                    $matchingFileNames[$fileInfo['filename']] = $fileInfo['filename'];
                }
            }

            $importProcessingDate->setLastProcessedDate($lastProcessedDate->format('Y-m-d'))
                ->save();

            $lastProcessedDate->add(new \DateInterval('P1D'));
        }

        return $matchingFileNames;
    }
}
