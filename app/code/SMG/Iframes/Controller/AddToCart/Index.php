<?php
namespace SMG\Iframes\Controller\AddToCart;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Framework\View\Result\PageFactory;
use \SMG\Iframes\Model\ContentSecurityPolicy;
use \Magento\Store\Model\StoreManagerInterface;

class Index extends Action
{
  protected $_resultPageFactory;
  protected $_productRepository;
  protected $_contentSecurityPolicy;
  protected $_storeManager;

  public function __construct(
    Context $context,
    ProductRepository $productRepository,
    PageFactory $resultPageFactory,
    ContentSecurityPolicy $contentSecurityPolicy,
    StoreManagerInterface $storeManager) {

    $this->_resultPageFactory = $resultPageFactory;
    $this->_productRepository = $productRepository;
    $this->_contentSecurityPolicy = $contentSecurityPolicy;
    $this->_storeManager = $storeManager;
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
        ->getBlock('SMG_addtocart_index')
        ->setData("product_id", $product->getId())
        ->setData("selected_product", $selectedProductId)
        ->setData("child_products", $childProducts)
        ->setData("quantity", $qty)
        ->setData("children_price", $priceOfChildren)
        ->setData("base_price", $product->getPrice())
        ->setData("base_product_id", $product->getId())
        ->setData("sku", $product->getSku())
        ->setData("drupalProductId", $product->getdrupalproductid())
        ->setData("desktop", $desktop);
      echo $block->toHtml();
    }
  }

  public function getBaseUrl() {
    return $this->_storeManager->getStore()->getBaseUrl();
  }
}
