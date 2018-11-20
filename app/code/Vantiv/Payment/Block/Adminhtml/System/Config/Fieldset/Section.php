<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Adminhtml\System\Config\Fieldset;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Helper\Js;

class Section extends Fieldset
{
    /**
     * Section constructor
     *
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Return header title part of html for payment solution
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading" >';
        $htmlId = $element->getHtmlId();

        $html .= '<div class="button-container"><button type="button"' .
            ' class="button action-configure' .
            '" id="' .
            $htmlId .
            '-head" onclick="toggleSection.call(this, \'' .
            $htmlId .
            "', '" .
            $this->getUrl(
                'adminhtml/*/state'
            ) . '\'); return false;"><span class="state-closed">' . __(
                'Configure'
            ) . '</span><span class="state-opened">' . __(
                'Close'
            ) . '</span></button>';

        $html .= '</div>';
        $html .= '<div class="heading"><strong>' . $element->getLegend() . '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">' . $element->getComment() . '</span>';
        }

        $html .= '<div class="config-alt"></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Add extra js to settings page
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.toggleSection = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                $(id).select('.comment').each(function(comment) {
                    $(comment).hide();
                });
                if ($(this).hasClassName('open')) {
                    $$('.with-button button.button').each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName('open')) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }
}
