<?php
namespace SMG\Iframes\Controller\AddToCart;

use Magento\Catalog\Helper\Product\View;
use \Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use \Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Controller\Product\View as Action;
use \Magento\Catalog\Api\ProductRepositoryInterface;

class Index extends Action
{
    protected $_pageFactory;
    protected $_productRepository;

    public function __construct(
        Context $context,
        View $viewHelper,
        ForwardFactory $resultForwardFactory,
        ProductRepositoryInterface $productRepository,
        PageFactory $pageFactory)
    {
        $this->_productRepository = $productRepository;
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context, $viewHelper, $resultForwardFactory, $pageFactory);
    }

    public function execute()
    {
        // Use sku from request to add the product id to the request
        $sku = $this->getRequest()->getParam('sku');
        $product = $this->_productRepository->get($sku);
        $requestParams = $this->getRequest()->getParams();
        $requestParams += ['id' => $product->getId()];
        $this->getRequest()->setParams($requestParams);
        return parent::execute();
    }
}
