<?php

namespace SMG\ParameterTypes\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;

Class LabelField extends Template {

    protected $_elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct (
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Form Element
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function prepareElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element) {
        $input = $this->_elementFactory->create("label", ['data' => $element->getData()]);

        // hide the element so there isn't a duplicate field
        $element->setData("value", '');

        $element->setData('after_element_html', $input->getElementHtml());

        // return
        return $element;
    }
}