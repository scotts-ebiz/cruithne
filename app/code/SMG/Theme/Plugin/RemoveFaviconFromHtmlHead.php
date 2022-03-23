<?php
/**
 * User: cnixon
 * Date: 3/10/22
 * Time: 4:19 PM
 */

namespace SMG\Theme\Plugin;

use Magento\Framework\View\Page\Config\Renderer;

class RemoveFaviconFromHtmlHead
{
    public function aroundPrepareFavicon(Renderer $subject, callable $proceed)
    {
        // Do not call $proceed() to remove favicon code
    }
}
