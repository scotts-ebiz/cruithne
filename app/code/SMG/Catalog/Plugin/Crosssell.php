<?php

namespace SMG\Catalog\Plugin;

use Psr\Log\LoggerInterface;

class Crosssell extends \Magento\Catalog\Block\Product\ProductList\Crosssell {

    protected $_logger;

    protected $_productsFactory;

    protected $_productsResource;

    /* Use Construct to:
     * Create a Factory Object for ProductFactory
     * Create a ResourceModel for Products
     * Create logger var to utilize for debugging as need be
    */

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ProductFactory $productsFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productsResource)
    {
        $this->_logger = $logger;
        $this->_productsFactory = $productsFactory;
        $this->_productsResource = $productsResource;
    }

    public function afterGetItems(\Magento\Catalog\Block\Product\ProductList\Crosssell $crosssell, $result)
    {
        $products = $this->_productsFactory->create();

        //Loop through $results
        foreach ($result as $crosssellItem) {
            //Get entity_id
            $productId = $crosssellItem->getData('entity_id');

            /* Use the resource model productResource to:
             * load all data for specific $productId
             * place in factory object $products
            */
            $this->_productsResource->load($products, $productId);

            //In the factory object $product select the value for 'short_description' and set it to $shortDescription
            $shortDescription = $products->getData('short_description');

            //For each $crosssellItem take the value of $shortDescription and set it to 'short_description'
            $crosssellItem->setData('short_description', $shortDescription);
        }
        return $result;
    }
}
