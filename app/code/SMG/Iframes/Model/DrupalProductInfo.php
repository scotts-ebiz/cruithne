<?php
namespace SMG\Iframes\Model;
use SMG\Iframes\Api\DrupalProductInfoInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Framework\Exception\NoSuchEntityException;

class DrupalProductInfo implements DrupalProductInfoInterface {

    private $_productRepository;

    public function __construct(ProductRepositoryInterface $productRepository) {
        $this->_productRepository = $productRepository;
    }

    /**
     * Returns product info for drupal uses
     *
     * @api
     * @param string $skus comma separated sku values.
     * @return array json of product info.
     */
    public function getInfo($skus) {

        // Return an empty array if we are not passed product Ids.
        if (empty($skus)) {
            return array();
        }

        $products = array();
        $skus = explode(',', $skus);

        foreach ($skus as &$sku) {

            // Grab the product from the database via the product Id.
            try {
                $product = $this->_productRepository->get($sku);
            } catch (NoSuchEntityException $e){
                $product = false;
            }

            // Do not include this product if we were passed a bad product Id
            if (!$product ||  $product->getId() === NULL) {
                continue;
            }

            //Grab child product ids if they exist
            $childProductIds = $product->getTypeInstance(true)->getChildrenIds($product->getId());

            if ($childProductIds != NULL && $product->getTypeId() !== 'grouped') {
                $childProductIds = $childProductIds[0];
            }

            if ($childProductIds != NULL && $product->getTypeId() == 'grouped') {
                $childProductIds = $childProductIds[3];
            }

            $childProducts = [];
            $defaultProductId = $product->getId();
            $isDefaultProductIdSet = false;

            //Create the child product array if applicable
            foreach( $childProductIds as $id ) {

                // Load child product model.
                $child = $this->_productRepository->getById($id);;

                // Check and see if this is the default child product
                if ($defaultProductId == $child->getId()) {
                    $isDefaultProductIdSet = true;
                }

                // Add relevant child product properties to our child products array.
                array_push($childProducts, array(
                    'sku' => $child->getSku(),
                    'price' => number_format((float)$child->getPrice(), 2, '.', ''),
                    'drupalProductId' => $product->getData('drupalproductid'),
                    'isDefault' => $child->getId() === $defaultProductId,
                    'size' => $product->getResource()->getAttribute('size')->getSource()->getOptionText($product->getData('size')),
                    'quantity' => $child->getExtensionAttributes()->getStockItem()->getQty()
                ));
            }

            // Set the default product Id to the first child if it wasn't explicitly set.
            if (!$isDefaultProductIdSet && $childProducts) {
                $childProducts[0]['isDefault'] = true;
            }

            // Add the data we want to our products array.
            array_push($products, array(
                'sku' => $product->getSku(),
                'drupalProductId' => $product->getdrupalproductid(),
                'price' => number_format((float)$product->getPrice(), 2, '.', ''),
                'size' => $product->getResource()->getAttribute('size')->getSource()->getOptionText($product->getData('size')),
                'type' => $product->getTypeId(),
                'childSkus' => $childProducts
                )
            );
        }

        return $products;
    }
}
