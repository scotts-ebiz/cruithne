<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field as ConfigField;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;

class RunTests extends ConfigField
{
    /**
     * @var string
     */
    private $runButtonId;

    /**
     * @var string
     */
    protected $_template = 'config/run-tests.phtml';

    /**
     * Constructor
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->runButtonId = $this->generateButtonId();
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }

    /**
     * Get button id
     *
     * @return string
     */
    private function generateButtonId()
    {
        $id = $this->getHtmlId() ? $this->getHtmlId() : '_' . uniqid();

        return 'runBtn' . $id;
    }

    /**
     * Get run button id
     *
     * @return string
     */
    public function getRunButtonId()
    {
        return $this->runButtonId;
    }

    /**
     * Get configuration for js component
     *
     * @return string
     */
    public function getMageInitJson()
    {
        return json_encode(
            [
                '*' => [
                    "Vantiv_Payment/js/certification/run-tests" => [
                        "run_button_id" => $this->getRunButtonId(),
                        "run_url" => $this->escapeUrl($this->getUrl('vantiv/certification/runTests')),
                        "run_redirect_url" => $this->escapeUrl($this->getUrl())
                    ]
                ]
            ]
        );
    }
}
