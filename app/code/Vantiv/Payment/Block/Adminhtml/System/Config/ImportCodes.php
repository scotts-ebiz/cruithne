<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field as ConfigField;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;

class ImportCodes extends ConfigField
{
    /**
     * @var string
     */
    private $importButtonId;

    /**
     * @var string
     */
    protected $_template = 'config/import-codes.phtml';

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
        $this->importButtonId = $this->importButtonId();
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
    private function importButtonId()
    {
        $id = $this->getHtmlId() ? $this->getHtmlId() : '_' . uniqid();

        return 'importBtn' . $id;
    }

    /**
     * Get import button id
     *
     * @return string
     */
    public function getImportButtonId()
    {
        return $this->importButtonId;
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
                    "Vantiv_Payment/js/giftcard/import-codes" => [
                        "import_button_id" => $this->getImportButtonId(),
                        "import_url" => $this->escapeUrl($this->getUrl('vantiv/giftCard/importCodes')),
                        "import_redirect_url" => $this->escapeUrl($this->getUrl())
                    ]
                ]
            ]
        );
    }
}
