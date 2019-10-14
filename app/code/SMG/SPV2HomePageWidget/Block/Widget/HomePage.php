<?php

namespace SMG\SPV2HomePageWidget\Block\Widget;

use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template;

class HomePage extends Template implements BlockInterface
{
	protected $_template = "widget/home-page.phtml";
}
