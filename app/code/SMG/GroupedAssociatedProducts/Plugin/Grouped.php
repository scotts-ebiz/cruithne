<?php
/** Experiment on a Plugin for Grouped.php **/

namespace SMG\GroupedAssociatedProducts\Plugin;

class Grouped extends \Magento\GroupedProduct\Block\Product\View\Type\Grouped {




  /*
  Take $result Array, loop through it, (get short_descript) add short_descript for each product, package in a
  productFactory, return $result
  */

  protected $_productsFactory;

  //Create a Factory Object to handle the array for $result this must be instantiated in a construct
  public function __construct(\SMG\GroupedAssociatedProducts\Model\ProductsFactory $productsFactory){
    $this -> _productsFactory = $productsFactory;
  }



  public function afterGetAssociatedProducts($result) {
      $products = $this->_productsFactory->create($result);




       //$shortDescription = $product->getData('short_description');

        //$product->setData('short_description');




    return $result;
  }


}








//foreach ($result as $item)
//{

// get the short description from product using $item->getId();
// need Factory and ResourceModel
// Create constructor
// Instatiate vars

//$product = $_productFactory->create();
//$_productResource->load($product, $item->getId());

//$shortDescription = $product->getData('short_description');

// add short description
//$item->setData('short_description', $shortDescription);
//}