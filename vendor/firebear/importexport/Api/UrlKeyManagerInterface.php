<?php
/**
 * UrlKeyManagerInterface
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api;

/**
 * Interface UrlKeyManagerInterface
 * @package Firebear\ImportExport\Api
 * @api
 * @since 3.1.4
 */
interface UrlKeyManagerInterface
{
    /**
     * @param $urlKey
     *
     * @return mixed
     */
    public function addUrlKeys($urlKey);

    /**
     * @return mixed
     */
    public function getUrlKeys();

    /**
     * @param $urlKey
     *
     * @return mixed
     */
    public function isUrlKeyExist($urlKey);
}