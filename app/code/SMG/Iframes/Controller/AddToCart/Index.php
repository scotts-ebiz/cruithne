<?php
namespace SMG\Iframes\Controller\AddToCart;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Framework\View\Result\PageFactory;
use \SMG\Iframes\Model\ContentSecurityPolicy;

class Index extends Action
{
  protected $_resultPageFactory;
  protected $_productRepository;
  protected $_contentSecurityPolicy;

  public function __construct(
    Context $context,
    ProductRepository $productRepository,
    PageFactory $resultPageFactory,
    ContentSecurityPolicy $contentSecurityPolicy) {

    $this->_resultPageFactory = $resultPageFactory;
    $this->_productRepository = $productRepository;
    $this->_contentSecurityPolicy = $contentSecurityPolicy;
    parent::__construct($context);
  }

  public function execute() {

    $this->_contentSecurityPolicy->setContentSecurityPolicy();

    $sku = $this->getRequest()->getParam('sku');
    $qty = $this->getRequest()->getParam('quantity',1);
    $desktop = $this->getRequest()->getParam('desktop', false);

    $product = $this->_productRepository->get($sku);

    if ($product === NULL) {
      return;
    }

    $selectedProductId = $product->getId();

    $childProductIds = $product->getTypeInstance(true)
      ->getChildrenIds($selectedProductId);

    if ($childProductIds != NULL) {
      $childProductIds = $childProductIds[0];
    }
    else {
      $childProductIds = [];
    }

    $childProducts = [];
    $priceOfChildren = 0.0;
    if ("bundle" == $product->getTypeId()) {
      $optionCollection = $product->getTypeInstance()->getOptionsCollection();
      $optionsIds = $product->getTypeInstance()->getOptionsIds();
      $selectionCollection = $product->getTypeInstance()->getSelectionsCollection($optionsIds);
      $options = $optionCollection->appendSelections($selectionCollection);
      foreach ($options as $option) {
        $selections = $option->getSelections();
        foreach ($selections as $selection) {
          $childPrice = $selection->getSelectionPriceValue();
          $childQty = $selection->getSelectionQty();
          $priceOfChildren += $childPrice * $childQty;
        }
      }
    }
    foreach( $childProductIds as $id ) {
      $child = $this->_productRepository->load( $id );
      $childProducts[] = $child;
    }

    if( $product ) {
      $resultPage = $this->_resultPageFactory->create();
      $block = $resultPage->getLayout()
        ->createBlock("SMG\Iframes\Block\AddToCart")
        ->setData("product_id", $product->getId())
        ->setData("selected_product", $selectedProductId)
        ->setData("child_products", $childProducts)
        ->setData("quantity", $qty)
        ->setData("children_price", $priceOfChildren)
        ->setData("base_price", $product->getPrice())
        ->setData("base_product_id", $product->getId())
        ->setData("sku", $product->getSku())
        ->setData("drupalProductId", $product->getdrupalproductid())
        ->setData("desktop", $desktop)
        ->setTemplate("SMG_Iframes::addToCart.phtml");
      echo $block->toHtml();
    }
  }

//  public function addAction() {
//
//    $productId = $this->getRequest()->getParam('product_id');
//    $qty = $this->getRequest()->getParam('quantity',1);
//    $params = $this->getRequest()->getParams();
//
//
//    $product = $this->_productRepository->setStoreId(Mage::app()
//      ->getStore()->getId())->load( $productId );
//
//    if ("bundle" == $product->getTypeId()) {
//      $cart = Mage::getModel('checkout/cart');
//      $cart->init();
//
//      $bundledItems = array();
//      $optionCollection = $product->getTypeInstance()
//        ->getOptionsCollection();
//      $optionsIds = $product->getTypeInstance()->getOptionsIds();
//      $selectionCollection = $product->getTypeInstance()
//        ->getSelectionsCollection($optionsIds);
//      $options = $optionCollection
//        ->appendSelections($selectionCollection);
//
//      foreach ($options as $option) {
//        $selections = $option->getSelections();
//
//        foreach ($selections as $selection) {
//          $bundledItems[$option->getOptionId()][] = $selection
//            ->getSelectionId();
//        }
//      }
//      $params = array(
//        'bundle_option' => $bundledItems,
//        'qty'           => $qty,
//        'product'       => $productId);
//
//      $product = new Mage_Catalog_Model_Product();
//      $product->load($productId);
//
//      $cart->addProduct($product, $params);
//      $cart->save();
//      /* $quote->addProduct($product, $params); */
//    } else {
//      // this was added after upgrade as the add to cart feature from drupal wasn't working
//      $cart = Mage::getModel('checkout/cart');
//      $cart->init();
//      $cart->addProduct($product, array('qty' => $qty));
//      $cart->save();
//    }
//
//    $this->_redirectUrl('/atgcart/add' . '?quantity=' . $qty . '&sku='
//      . $product->getSku());
//  }
}
