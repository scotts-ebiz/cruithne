<?php

namespace SMG\SPV2HeroWidget\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class HeroWidget extends Template implements BlockInterface
{
    protected $_template = "widget/hero.phtml";
}
