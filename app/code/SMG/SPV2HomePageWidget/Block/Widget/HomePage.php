<?php

namespace SMG\SPV2HomePageWidget\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class HomePage extends Template implements BlockInterface
{
    protected $_template = "widget/home-page.phtml";
}
