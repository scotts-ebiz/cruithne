<?php

namespace SMG\HeroCallout\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class Herocallout extends Template implements BlockInterface {

    protected function _toHtml() {
		$html ='<section class="connect-home col-2">
			<div class="content">
				<h2>' . $this->getData('bannerheadline') . '</h2>
				<p>' . $this->getData('bannertext') . '</p>
				<p class="highlight">' . $this->getData('bannertexthighlight') . '</p>';
				if($this->getData('buttontext')):
					$html .= '<a class="button" href="' . $this->getData('buttonlink') . '">' . $this->getData('buttontext') . '</a>';
				endif;
			$html .= '</div>
		</section>';
		$html .= '<style type="text/css">';
		if($this->getData('bannerimagemobile') != ''):
			$html .= '.connect-home { background-image: url(/media/' . $this->getData('bannerimagemobile') . '); }';
		endif;
		if($this->getData('bannerimage') != ''):
			$html .= '@media screen and (min-width: 1024px) { .connect-home { background-image: url(/media/' . $this->getData('bannerimage') . '); } }';
		endif;
		$html .= '</style>';
		
        return $html;
    }
	
}