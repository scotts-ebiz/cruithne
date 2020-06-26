<?php
namespace SMG\Iframes\Api;

interface DrupalProductInfoInterface {
    /**
     * Returns product info for drupal uses
     *
     * @api
     * @param string $skus comma separated sku values.
     * @return mixed
     */
    public function getInfo($skus);
}