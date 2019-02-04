<?php
namespace Freshrelevance\Digitaldatalayer\Model\Config\Source;

class StorePages implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'cms_index_index', 'label' => __('Home page')],
            ['value' => 'catalog_product_view', 'label' => __('Product page')],
            ['value' => 'catalog_product_compare_index', 'label' => __('Products Compare page')],
            ['value' => 'catalog_category_view', 'label' => __('Category page')],
            ['value' => 'catalogsearch_result_index', 'label' => __('Search page')],
            ['value' => 'checkout_cart_index', 'label' => __('Basket page')],
            ['value' => 'checkout_onepage_index', 'label' =>__('Checkout page')],
            ['value' => 'checkout_onepage_success', 'label' =>__('Checkout success page')],
            ['value' => 'cms_page', 'label' =>__('General CMS page')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $output = [];
        foreach ($this->toOptionArray() as $item) {
            $output[$item['value']] = $item['label'];
        }
        return $output;
    }
}
