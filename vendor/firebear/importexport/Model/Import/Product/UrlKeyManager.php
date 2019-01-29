<?php
/**
 * UrlKeyManager
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product;

use Firebear\ImportExport\Api\UrlKeyManagerInterface;

/**
 * Class UrlKeyManager
 * @package Firebear\ImportExport\Model\Import\Product
 * @api
 * @since 3.1.4
 */
class UrlKeyManager implements UrlKeyManagerInterface
{
    protected $importUrlKeys = [];

    /**
     * @param $urlKey
     *
     * @return $this
     */
    public function addUrlKeys($urlKey)
    {
        if (!\in_array($urlKey, $this->importUrlKeys)) {
            $this->importUrlKeys[] = $urlKey;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getUrlKeys()
    {
        return $this->importUrlKeys;
    }

    /**
     * @param $urlKey
     *
     * @return bool
     */
    public function isUrlKeyExist($urlKey)
    {
        if (\in_array($urlKey, $this->importUrlKeys)) {
            return true;
        }
        return false;
    }
}
