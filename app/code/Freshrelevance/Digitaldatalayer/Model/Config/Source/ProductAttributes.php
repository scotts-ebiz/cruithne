<?php
namespace Freshrelevance\Digitaldatalayer\Model\Config\Source;

class ProductAttributes implements \Magento\Framework\Option\ArrayInterface
{
    private $prodModel;
    private $mageConfig;

    public function __construct(
        \Magento\Catalog\Model\Product $prodModel,
        \Magento\Eav\Model\Config $mageConfig
    ) {
        $this->productModel = $prodModel;
        $this->config = $mageConfig;
    }
    public function toOptionArray()
    {
        $attributes = $this->productModel->getAttributes();
        $attributeArray = [['label' => 'none', 'value' => '0'], ['label' => 'all', 'value' => 'all']];
        foreach ($attributes as $a) {
            $attrCode=$a->getAttributeCode();
            $attribute_details = $this->config
                ->getAttribute('catalog_product', $attrCode);
            if ($attribute_details->getData('is_user_defined')) {
                array_push($attributeArray, ['label' => $attrCode, 'value' => $attrCode]);
            }
        }

        return $attributeArray;
    }
}
