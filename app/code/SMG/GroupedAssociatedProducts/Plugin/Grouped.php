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

      $productId = $result->getData('entity_id');

      // - Why do I need the entity_id?

      // - Do I need to use the load() function? Why?

      // - I think I still need a foreach loop to loop through each product to get the short_decript and set a value
      // to it. $results->setData('short_description', $shortDescription);


      //Q - How do I now do something w/ $result? I'll need to do some sort of foreach for short_descript

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