<?php

namespace SMG\ParameterTypes\Block\Adminhtml\Widget;

use Magento\Framework\Data\Form\Element\AbstractElement as Element;
use Magento\Backend\Block\Template\Context as TemplateContext;
use Magento\Framework\Data\Form\Element\Factory as FormElementFactory;
use Magento\Backend\Block\Template;

class ImageChooser extends Template {
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    /**
     * @param TemplateContext $context
     * @param FormElementFactory $elementFactory
     * @param array $data
     */
    public function __construct (TemplateContext $context, FormElementFactory $elementFactory, $data = []) {
        $this->elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param Element $element
     * @return Element
     * @throws
     */
    public function prepareElementHtml(Element $element) {
        $config = $this->_getData('config');

        // this will get the desired preview html if the image
        // already exists
        $previewHtml = $this->getPreviewHtml($element);

        // this will get the input text box for the image
        /** @var \Magento\Framework\Data\Form\Element\Text $input */
        $input = $this->elementFactory->create("text", ['data' => $element->getData()]);
        $input->setId($element->getId());
        $input->setForm($element->getForm());
        $input->setClass("widget-option input-text admin__control-text");

        if ($element->getRequired()) {
            $input->addClass('required-entry');
        }

        // this will get the choose button
        $prefix = $element->getForm()->getHtmlIdPrefix();
        $elementId = $prefix . $element->getId();

        $sourceUrl = $this->getUrl('cms/wysiwyg_images/index', ['target_element_id' => $elementId, 'type' => 'file']);

        /** @var \Magento\Backend\Block\Widget\Button $chooser */
        $chooser = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setType('button')
            ->setClass('btn-chooser')
            ->setLabel(__('Choose Image'))
            ->setOnClick('MediabrowserUtility.openDialog(\''. $sourceUrl .'\')')
            ->setDisabled($element->getReadonly());

        // this will get the delete button
        $removeButton = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setType('button')
            ->setClass('btn-delete')
            ->setLabel(__('Remove Image'))
            ->setOnclick('document.getElementById(\''. $elementId .'\').value=\'\';if(document.getElementById(\''. $elementId .'_image\'))document.getElementById(\''. $elementId .'_image\').parentNode.remove()')
            ->setDisabled($element->getReadonly());

        // hide the element so there isn't a duplicate field
        $element->setData("value", '');

        // add to the after html element
        $element->setData('after_element_html', '<div class="img_chooser_control">' . $input->getElementHtml() . $previewHtml . $chooser->toHtml() . $removeButton->toHtml() . "</div>");

        // return
        return $element;
    }

    /**
     * Gets the preview html to display a preview the image
     * that was selected.
     *
     * @param Element $element
     * @return string
     */
    private function getPreviewHtml(Element $element) {
        $previewHtml = '';
        if ($element->getEscapedValue()) {
            // Add image preview.
            $url = $element->getEscapedValue();

            $previewHtml = '<a href="' . $url . '"'
                . ' onclick="imagePreview(\'' . $element->getHtmlId() . '_image\'); return false;">'
                . '<img src="' . $url . '" id="' . $element->getHtmlId() . '_image" title="' . $element->getEscapedValue() . '"'
                . ' alt="' . $element->getEscapedValue() . '" height="40" class="small-image-preview v-middle"'
                . ' style="margin-top:7px; border:1px solid grey" />'
                . '</a> ';
        }

        // return
        return $previewHtml;
    }
}