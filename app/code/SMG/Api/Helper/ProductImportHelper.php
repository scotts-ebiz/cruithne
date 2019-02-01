<?php

namespace SMG\Api\Helper;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogSearch\Model\AdvancedFactory;
use Magento\CatalogSearch\Model\ResourceModel\Advanced as AdvancedResource;
use Psr\Log\LoggerInterface;

class ProductImportHelper
{
    // Input JSON File Constants
    const INPUT_MATERIAL_NUMBER = "Material_Number";
    const INPUT_DESC_1 = "Desc_1";
    const INPUT_DESC_2 = "Desc_2";
    const INPUT_EA_UPC = "EA_UPC";
    const INPUT_MATERIAL_GROUP = "Material_Group";
    const INPUT_GROSS_WEIGHT = "Gross_Weight";
    const INPUT_NET_WEIGHT = "Net_Weight";
    const INPUT_WEIGHT_UOM = "Weight_UOM";
    const INPUT_VOLUME = "Volume";
    const INPUT_VOLUME_UOM = "Vol_UOM";
    const INPUT_COLOR = "Color";
    const INPUT_MATERIAL_DIVISION = "Material_Division";
    const INPUT_LENGTH = "Length";
    const INPUT_WIDTH = "Width";
    const INPUT_HEIGHT = "Height";
    const INPUT_DIMENSION_UOM = "Dimension_UOM";
    const INPUT_PRODUCT_HIERARCHY = "Product_Hierarchy";
    const INPUT_MATERIAL_STATUS = "Material_Status";
    const INPUT_BRAND = "Brand";
    const INPUT_SUB_BRAND = "Sub_Brand";

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var ProductResource
     */
    protected $_productResource;

    /**
     * @var AdvancedFactory
     */
    protected $_advancedFactory;

    /**
     * @var AdvancedResource
     */
    protected $_advancedResource;

    /**
     * ProductImporteHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     * @param AdvancedFactory $advancedFactory
     * @param AdvancedResource $advancedResource
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        ProductFactory $productFactory,
        ProductResource $productResource,
        AdvancedFactory $advancedFactory,
        AdvancedResource $advancedResource)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;
        $this->_advancedFactory = $advancedFactory;
        $this->_advancedResource = $advancedResource;
    }

    /**
     * Handles the product import
     *
     * @param $requestData
     * @return string
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function processProductImport($requestData)
    {
        // variables
        $orderStatusResponse = $this->_responseHelper->createResponse(true, "The product import process completed successfully.");

        // make sure that we were given something from the request
        if (!empty($requestData))
        {
            // loop through the orders that were sent via the JSON file
            foreach ($requestData as $product)
            {
                // process the product info
                $this->processProductInfo($product);
            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\ProductImportHelper - Nothing was provided to process.");

            $orderStatusResponse = $this->_responseHelper->createResponse(false, 'Nothing was provided to process.');
        }

        // return
        return $orderStatusResponse;
    }

    private function processProductInfo($product)
    {
        // check to see if there is an a sku
        $sku = $product[self::INPUT_MATERIAL_NUMBER];
        if ($sku)
        {
            // get the product entity
            $advanced = $this->_advancedFactory->create();
            $this->_advancedResource->load($advanced, $sku, 'sku');

            // get the product from the database
            /**
             * @var \Magento\Catalog\Model\Product $productData
             */
            $productData = $this->_productFactory->create();
            $this->_productResource->load($productData, $advanced->getData('entity_id'));

            // get the store id
            $storeId = $productData->getStoreId();

            // get the attributes for updating
            $attributes = $productData->getAttributes();

            // update the weight
            $productData->addAttributeUpdate($attributes['weight']->getAttributeCode(), $product[self::INPUT_NET_WEIGHT], $storeId);

            // update the product weight uom
            $productData->addAttributeUpdate($attributes['product_weight_uom']->getAttributeCode(), $product[self::INPUT_WEIGHT_UOM], $productData->getStoreId());

            // update the volume
            $productData->addAttributeUpdate($attributes['product_volume']->getAttributeCode(), $product[self::INPUT_VOLUME], $productData->getStoreId());

            // update the product volume uom
            $productData->addAttributeUpdate($attributes['product_volume_uom']->getAttributeCode(), $product[self::INPUT_VOLUME_UOM], $productData->getStoreId());

            // update the length
            $productData->addAttributeUpdate($attributes['ts_dimensions_length']->getAttributeCode(), $product[self::INPUT_LENGTH], $productData->getStoreId());
            //$productData->addAttributeUpdate($attributes['product_length']->getAttributeCode(), $product[self::INPUT_LENGTH], $productData->getStoreId());

            // update the width
            $productData->addAttributeUpdate($attributes['ts_dimensions_width']->getAttributeCode(), $product[self::INPUT_WIDTH], $productData->getStoreId());
            //$productData->addAttributeUpdate($attributes['product_width']->getAttributeCode(), $product[self::INPUT_WIDTH], $productData->getStoreId());

            // update the height
            $productData->addAttributeUpdate($attributes['ts_dimensions_height']->getAttributeCode(), $product[self::INPUT_HEIGHT], $productData->getStoreId());
            //$productData->addAttributeUpdate($attributes['product_height']->getAttributeCode(), $product[self::INPUT_HEIGHT], $productData->getStoreId());

            // update the dimension uom
            $productData->addAttributeUpdate($attributes['product_dimension_uom']->getAttributeCode(), $product[self::INPUT_DIMENSION_UOM], $productData->getStoreId());

            // update the brand
            $productData->addAttributeUpdate($attributes['product_zz_brand']->getAttributeCode(), $product[self::INPUT_BRAND], $productData->getStoreId());

            // update the sub brand
            $productData->addAttributeUpdate($attributes['product_zz_subbrand']->getAttributeCode(), $product[self::INPUT_SUB_BRAND], $productData->getStoreId());
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\ProductImportHelper - Missing sku in file.");
        }
    }
}