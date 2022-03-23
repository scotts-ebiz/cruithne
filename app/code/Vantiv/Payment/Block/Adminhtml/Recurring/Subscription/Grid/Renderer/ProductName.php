<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer;

class ProductName extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @inheritdoc
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($this->_authorization->isAllowed('Magento_Catalog::products') && $row->getProductId()) {
            return sprintf(
                '<a href="%s" class="product-link"><span>%s</span></a>',
                $this->getUrl('catalog/product/edit', ['id' => $row->getProductId()]),
                $this->escapeHtml($row->getProductName())
            );
        }

        return $this->escapeHtml($row->getProductName());
    }
}
