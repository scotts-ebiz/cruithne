<?php
/** Experiment on a Plugin for Grouped.php **/

namespace SMG\GroupedAssociatedProducts\Plugin;

class Grouped extends \Magento\GroupedProduct\Block\Product\View\Type\Grouped {


  protected $_productsFactory;


  //Create a Factory Object to handle the array for $result this must be instantiated in a construct
  public function __construct(\SMG\GroupedAssociatedProducts\Model\ProductsFactory $productsFactory){
    $this->_productsFactory = $productsFactory;
  }



  // Q - Do I need to load $results into function?
  public function afterGetAssociatedProducts($result) {


      //Create instance of $_productsFactory factory object and call it $products and put $results into it
      // Q - Do I need to load $results into factory object, if dont what data can it play with?
      $products = $this->_productsFactory->create();


      /*
      1. Do I get entity_id? Why? From where? Do I use load(). How do $results have short_description and yet
      I need to assign short_description to getAssociatedProduct $results?

      2. I need short_description how to I get that, from where?

      3. I need to load short_description in the results of associatedProducts, how do I do that?
      foreach?

      */


      //Get entity_id from $products factory object
      $productId = $products->getData('entity_id');


      //Get short_description from $products factory object
      $shortDescription = $products->getData('short_description');


      $productResourceModel->load($products, $productId);








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