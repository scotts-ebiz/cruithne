<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\OrderCreateGrid\Block\Adminhtml\Order\Create\Search\Grid\Renderer;

/**
 * Adminhtml sales create order product search grid product name column renderer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Weight extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    /**
     * Render product name to add Configure link
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
		if($row->getWeight()){
          $row->setWeight($row->getWeight());
        }
       return (float) parent::render($row);
    }
}
