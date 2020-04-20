<?php

namespace SMG\SPV2HomePageHeroWidget\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class HomePageHeroWidget extends Template implements BlockInterface
{
    protected $_template = "widget/homepagehero.phtml";

    /**
     * @param string $size
     * @param string $alignment
     * @return string
    */
    public function setHorizontalAlignment($size, $alignment)
    {
        $prefix = ($size == "mobile") ? "xs" : "md";
        switch ($alignment) {
            case "center":
                return $prefix . ':sp-text-center';
                break;
            case "right":
                return $prefix . ':sp-text-right';
                break;
            default: // left
                return $prefix . ':sp-text-left';
        }
    }

    /**
     * @param string $size
     * @param string $alignment
     * @return string
     */
    public function setHorizontalFlexAlignment($size, $alignment)
    {
        $prefix = ($size == "mobile") ? "xs" : "md";
        switch ($alignment) {
            case "center":
                return $prefix . ':sp-justify-center';
                break;
            case "right":
                return $prefix . ':sp-justify-end';
                break;
            default: // left
                return $prefix . ':sp-justify-start';
        }
    }

    /**
     * @param string $size
     * @param string $alignment
     * @return string
     */
    public function setVerticalAlignment($size, $alignment) {
        $prefix = ($size == "mobile") ? "xs" : "md";
        switch ($alignment) {
            case 'top':
                return $prefix . ':sp-items-start';
                break;
            case 'bottom':
                return $prefix . ':sp-items-end';
                break;
            default: // middle
                return $prefix . ':sp-items-center';
        }
    }
}
