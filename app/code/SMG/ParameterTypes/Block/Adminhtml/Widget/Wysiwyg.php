<?php

namespace SMG\ParameterTypes\Block\Adminhtml\Widget;

use \Magento\Backend\Block\Template;
use \Magento\Backend\Block\Template\Context;
use \Magento\Framework\Data\Form\Element\Factory;
use \Magento\Cms\Model\Wysiwyg\Config;
use \Magento\Framework\Data\Form\Element\AbstractElement;

Class Wysiwyg extends Template
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $wysiwygConfig;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $factoryElement;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $factoryElement
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(Context $context, Factory $factoryElement, Config $wysiwygConfig, $data = []) {
        $this->factoryElement = $factoryElement;

        $this->wysiwygConfig = $wysiwygConfig;

        parent::__construct($context, $data);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Form Element
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function prepareElementHtml(AbstractElement $element) {
        $editor = $this->factoryElement->create('editor', ['data' => $element->getData()])
            ->setLabel('')
            ->setForm($element->getForm())
            ->setWysiwyg(true)
            ->setConfig(
                $this->wysiwygConfig->getConfig([
                    'add_variables' => false,
                    'add_widgets' => false,
                    'add_images' => false
                ])
            );

        if ($element->getRequired()) {
            $editor->addClass('required-entry');
        }

        $element->setData('after_element_html', $editor->getElementHtml());
        $element->setValue(''); // Hides the additional label that gets added.

        // return
        return $element;
    }
}