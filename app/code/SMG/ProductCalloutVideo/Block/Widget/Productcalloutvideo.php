<?php

namespace SMG\ProductCalloutVideo\Block\Widget;

//Creates a shorthand for that you can just use Template in the statement below
use Magento\Framework\View\Element\Template;

//Creates a shorthand for that you can just use BlockInterface in the statement below
use Magento\Widget\Block\BlockInterface;



class Productcalloutvideo extends Template implements BlockInterface {


  protected function _toHtml() {
    $html = '<section class="product-callout-video-widget product-info-container align-top">' . trim($this->getData
      ('videoembedcode')) . '<div class="wenis">qwerqwer</div>' . '</section>';
    return $html;
  }
}
