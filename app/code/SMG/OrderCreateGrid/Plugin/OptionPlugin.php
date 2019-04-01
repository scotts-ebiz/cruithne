<?php

namespace SMG\OrderCreateGrid\Plugin;

class OptionPlugin
{
    
  protected $_productloader;  

  public function __construct(
        \Magento\Catalog\Model\ProductFactory $_productloader

    ) {
        $this->_productloader = $_productloader;
    }

    public function aroundGetSelectionTitlePrice(
        \Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option $subject,
        \Closure $proceed,
        $selection,
        $includeContainer = true)
    {
       $productid = $selection->getId();
       $productdata = $this->getLoadProduct($productid);
       $priceTitle = '<span class="product-name">' . $subject->escapeHtml($selection->getName()) . '</span>';
       
       $priceTitle .= ' &nbsp; ' . ($includeContainer ? '<span class="price-notice">' : '')
            . $subject->renderPriceString($selection, $includeContainer) . ($includeContainer ? '</span>' : '')
            .'';
        $priceTitle .= '<br/>';   
        $priceTitle .= '<span class="Sku"> SKU : ' . $subject->escapeHtml($selection->getSku()) . '</span>'; 
        $priceTitle .= '<br/>';  
        $priceTitle .= '<span class="Weight"> Weight : ' . $subject->escapeHtml($productdata->getWeight()) . '</span>';    
        return $priceTitle;
    }
    
    public function getLoadProduct($id)
    
    {
        return $this->_productloader->create()->load($id);
    }
}
