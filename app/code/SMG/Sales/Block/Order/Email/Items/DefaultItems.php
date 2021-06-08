<?php
namespace SMG\Sales\Block\Order\Email\Items;
use Magento\Catalog\Model\ProductFactory;

class DefaultItems extends \Magento\Sales\Block\Order\Email\Items\DefaultItems
{
    public $productFactory;
    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ProductFactory $productFactory,
        array $data = []
    )
    {
        $this->productFactory = $productFactory;

        parent::__construct($context, $data);
    }
	
	public  function getProductModel($id)
    {
        $product = $this->productFactory->create()->load($id);
        return $product;
    }
}