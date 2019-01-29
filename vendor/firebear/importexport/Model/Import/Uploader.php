<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\Framework\Filesystem\DriverPool;

/**
 * Class Uploader
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @package Firebear\ImportExport\Model\Import
 */
class Uploader extends \Magento\CatalogImportExport\Model\Import\Uploader
{
    private $httpScheme = 'http://';

    /**
     * Uploader constructor.
     *
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb
     * @param \Magento\MediaStorage\Helper\File\Storage $coreFileStorage
     * @param \Magento\Framework\Image\AdapterFactory $imageFactory
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\File\ReadFactory $readFactory
     * @param \Firebear\ImportExport\Model\Filesystem\File\ReadFactory $fireReadFactory
     * @param null $filePath
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb,
        \Magento\MediaStorage\Helper\File\Storage $coreFileStorage,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory,
        \Firebear\ImportExport\Model\Filesystem\File\ReadFactory $fireReadFactory,
        $filePath = null
    ) {
        parent::__construct(
            $coreFileStorageDb,
            $coreFileStorage,
            $imageFactory,
            $validator,
            $filesystem,
            $readFactory,
            $filePath
        );

        $this->_readFactory = $fireReadFactory;
    }

    /**
     * @param string $fileName
     * @param bool $renameFileOff
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function move($fileName, $renameFileOff = false)
    {
        if ($renameFileOff) {
            $this->setAllowRenameFiles(false);
        }
        $fileName = trim($fileName);
        if (preg_match('/\bhttps?:\/\//i', $fileName, $matches)) {
            $file_info = new \finfo(FILEINFO_MIME_TYPE);
            $url = str_replace($matches[0], '', $fileName);
            $urlProp = $this->parseUrl($this->httpScheme . $url);
            $hostname = $urlProp['host'];
            $path = $urlProp['path'];
            $path1 = \explode('/', $path);
            $name = str_replace('/', '_', end($path1));
            if (!$this->isImageQueryString($fileName)) {
                $ch = curl_init();
                $url = $this->httpScheme . $hostname . $path;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                if (isset($urlProp['user'], $urlProp['pass'])) {
                    curl_setopt($ch, CURLOPT_USERPWD, $urlProp['user'] . ':' . $urlProp['pass']);
                }
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, 1);
                $data = curl_exec($ch);
                $info = curl_getinfo($ch);

                if ($data === false) {
                    //error_log("cURL Error: " . curl_error($ch));
                }
                curl_close($ch);
                $mime_type = $file_info->buffer($data);
            } else {
                $data = $this->_directory->getDriver()->fileGetContents($fileName);
                $mime_type = $file_info->buffer($data);
                if (!array_key_exists(\pathinfo($name, PATHINFO_EXTENSION), $this->_allowedMimeTypes)) {
                    $name .= $this->getImageMimeType($mime_type);
                }
            }
            $this->_directory->writeFile('var/cache/' . $name, $data);
            $read = $this->_readFactory->create(
                $this->_directory->getAbsolutePath() . 'var/cache/' . $name,
                DriverPool::FILE
            );
//            $fileName = preg_replace('/[^a-z0-9\._-]+/i', '', $fileName);
            $fileName = $name;
            if (!array_key_exists(\pathinfo($fileName, PATHINFO_EXTENSION), $this->_allowedMimeTypes)) {
                $fileName .= $this->getImageMimeType($mime_type);
            }
            $this->_directory->writeFile(
                $this->_directory->getRelativePath($this->getTmpDir() . '/' . $fileName),
                $read->readAll()
            );
        }

        $filePath = $this->_directory->getRelativePath($this->getTmpDir() . '/' . $fileName);

        $this->_setUploadFile($filePath);

        $destDir = $this->_directory->getAbsolutePath($this->getDestDir());
        $result = $this->save($destDir);

        unset($result['path']);

        $result['name'] = self::getCorrectFileName($result['name']);
        return $result;
    }

    /**
     * @param string $mime_type
     *
     * @return string
     */
    private function getImageMimeType($mime_type = 'image/jpeg')
    {
        $fileExtension = \array_flip($this->_allowedMimeTypes)[$mime_type] ?? 'jpeg';
        return '.' . $fileExtension;
    }

    /**
     * @param $fileName
     *
     * @return bool
     */
    private function isImageQueryString($fileName)
    {
        return \strpos($fileName, '?') !== false;
    }

    /**
     * Create folder
     *
     * @param string $directory
     *
     * @return \Magento\Framework\File\Uploader
     * @throws \Exception
     */
    public function createDirectory($directory)
    {
        if (!$directory || !$this->_allowCreateFolders) {
            return $this;
        }

        $directory = (substr($directory, -1) == '/') ? substr($directory, 0, -1) : $directory;

        if (!(@is_dir($directory)
            || @mkdir($directory, 0777, true)
        )) {
            throw new \Exception("Unable to create directory '{$directory}'.");
        }
        return $this;
    }

    /**
     * @param $directory
     *
     * @return bool
     */
    public function isDirectoryWritable($directory)
    {
        return $this->_directory->isWritable($directory);
    }

    protected function parseUrl($path)
    {
        return parse_url($path);
    }
}
