<?php

namespace SMG\GroupedAssociatedProducts\Plugin;

class Grouped extends \Magento\GroupedProduct\Block\Product\View\Type\Grouped {


  protected $_productsFactory;

  protected $_productResource;


  /* Use Constructor to:
   * Create a Factory Object to handle the array for $result this must be instantiated in a construct
   * Create a ResourceModel for Products
  */

  public function __construct(\SMG\GroupedAssociatedProducts\Model\ProductsFactory $productsFactory,
  \Magneto\GroupedProduct\Model\ResourceModel\Product $productsResource
  ){
      $this->_productsFactory = $productsFactory;

      $this->_productResource = $productsResource;
  }


  public function afterGetAssociatedProducts($result) {

      $products = $this->_productsFactory->create();

      foreach ($result as $associatedProductItem) {
          $productId = $result->getData('entity_id');


          //Use the productResource to select the $productId and place it in $products Factory object
          $this->_productResource->load($products, $productId);


          //In the factory object select the valye for 'short_description' and set it to $shortDescription
          $shortDescription = $products->getData('short_description');


          //For each $associatedProductItem take the value of $shortDescription and set it to 'short_description'
          $associatedProductItem->setData('short_description', $shortDescription);
      }

    return $result;
  }
}
