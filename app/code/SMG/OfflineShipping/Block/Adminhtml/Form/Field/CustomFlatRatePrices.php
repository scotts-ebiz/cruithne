<?php
/**
 * @category    ClassyLlama
 * @copyright   Copyright (c) 2019 Classy Llama Studios, LLC
 */

namespace SMG\OfflineShipping\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use SMG\OfflineShipping\Block\Adminhtml\Form\Column\LabelRendererFactory;

class CustomFlatRatePrices extends AbstractFieldArray
{
    /**
     * @var LabelRendererFactory
     */
    protected $labelRendererFactory;

    /**
     * @var \SMG\OfflineShipping\Model\Config\Source\Method
     */
    protected $source;

    /**
     * @var string
     */
    protected $_template = 'SMG_OfflineShipping::system/config/form/field/array.phtml';

    /**
     * CustomFlatRatePrices constructor.
     *
     * @param \Magento\Backend\Block\Template\Context         $context
     * @param \SMG\OfflineShipping\Model\Config\Source\Method $source
     * @param LabelRendererFactory                            $labelRendererFactory
     * @param array                                           $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \SMG\OfflineShipping\Model\Config\Source\Method $source,
        LabelRendererFactory $labelRendererFactory,
        array $data = []
    ) {
        $this->_addAfter = false;
        $this->source = $source;
        $this->labelRendererFactory = $labelRendererFactory;
        parent::__construct($context, $data);
    }

    /**
     * @param string $name
     * @param array  $params
     */
    public function addColumn($name, $params)
    {
        parent::addColumn($name, $params);
        $this->_columns[$name]['editable'] = $params['editable'] ?? true;
        $this->_columns[$name]['input_type'] = $params['input_type'] ?? 'text';
    }

    /**
     * {@inheritDoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'shipping_method',
            [
                'label' => __('Shipping method'),
                'editable' => false,
                'renderer' => $this->labelRendererFactory->create()
            ]
        );
        $this->addColumn(
            'rate',
            [
                'label' => __('Rate'),
                'class' => 'validate-number validate-zero-or-greater',
                'editable' => true
            ]
        );
    }

    /**
     * This method pre-populates the 'Shipping Method' column of each row from
     * \SMG\OfflineShipping\Model\Config\Source\Method::getAvailableMethods and preps the "Rate" column if necessary
     *
     * @return array
     */
    public function getArrayRows()
    {
        /** @var /** @var \Magento\Framework\Data\Form\Element\AbstractElement $element */
        $element = $this->getElement();
        $value = empty($element->getValue()) ? [] : $element->getValue();
        $availableMethods = $this->source->getAvailableMethods();

        foreach ($availableMethods as $methodCode => $methodLabel) {
            $value[$methodCode]['shipping_method'] = $methodLabel;
            if (!isset($value[$methodCode]['rate'])) {
                $value[$methodCode]['rate'] = null;
            }
        }

        // remove any methods that were potentially removed from the availableMethods array
        $value = \array_filter(
            $value,
            function ($code) use ($availableMethods) {
                return \in_array($code, \array_keys($availableMethods));
            },
            ARRAY_FILTER_USE_KEY
        );

        $element->setValue($value);

        return parent::getArrayRows();
    }

    /**
     * @return string
     */
    public function getHtmlId()
    {
        return 'custom_flat_rate_shipping';
    }

    /**
     * @return bool
     */
    public function allowAddRow()
    {
        return false;
    }
}
