<?php

namespace SMG\ParameterTypes\Block\Plugin\Helper\Wysiwyg;

class Images
{
    public function afterGetImageHtmlDeclaration(\Magento\Cms\Helper\Wysiwyg\Images $instance, $result, $filename) {
        // get the path of the URL without the domain to make it relative path
        $path = parse_url($instance->getCurrentUrl(), PHP_URL_PATH);

        // get the path and file name together for the entire relative path
        $fileUrl = $path . $filename;

        // return
        return $fileUrl;
    }
}