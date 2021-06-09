<?php

namespace SMG\Zaius\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zaius\Engage\Helper\Data;
use Zaius\Engage\Helper\Sdk as Sdk;
use ZaiusSDK\ZaiusException;

class ApiCartAddHelper
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var QuoteResource
     */
    protected $_quoteResource;

    /**
     * @var sdk
     */
    protected $_sdk;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * CartAddObserver constructor.
     *
     * @param ProductRepository $productRepository
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param QuoteFactory $quoteFactory
     * @param QuoteResource $quoteResource
     * @param Sdk $sdk
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        LoggerInterface $logger,
        ProductRepository $productRepository,
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource,
        Sdk $sdk,
        StoreManagerInterface $storeManager
    )
    {
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_productRepository = $productRepository;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteResource = $quoteResource;
        $this->_sdk = $sdk;
        $this->_storeManager = $storeManager;
    }

    /**
     * Adds the product from the quote to zaius
     *
     * @param Product $product
     * @param $quoteId
     * @return $this
     */
    public function addToCart(Product $product, $quoteId)
    {
        try
        {
            /** @var Quote $quote */
            $quote = $this->_quoteFactory->create();
            $this->_quoteResource->load($quote, $quoteId);

            $info = $product->getQty();

            /** @var Product $product */
            // when working with configurable/simple products, product/item models grab the configurable parent of a
            // simple product, but contain the sku of the simple product. We need to grab that sku, and load the simple
            // product object for processing to Zaius.
            // travis@trellis.co
            $sku = $product->getSku();
            $simpleProduct = $this->_productRepository->get($sku);
            $id = $simpleProduct->getId();

            $quoteHash = $this->_helper->encryptQuote($quote);
            $baseUrl = $this->_storeManager->getStore($quote->getStoreId())->getBaseUrl();

            // Identifiers
            $vuid = $this->_helper->getVuid();
            $zm64_id = $this->_helper->getZM64_ID();
            $identifiers = array_filter(compact('vuid', 'zm64_id'));
            if (empty($identifiers))
            {
                $identifiers = array('quote_id' => $quoteId);
            }

            $eventData = [
                'product_id' => $this->_helper->getProductId($simpleProduct),
                'category' => $this->_helper->getCurrentOrDeepestCategoryAsString($simpleProduct),
                'zaius_alias_cart_id' => $quote->getId(),
                'valid_cart' => $this->_helper->isValidCart($quote),
                'ts' => time()
            ];

            if (isset($quoteHash))
            {
                $eventData['cart_id'] = $quote->getId();
                $eventData['cart_hash'] = $quoteHash;
            }

            $vtsrc = $this->_helper->getVTSRC();
            if ($vtsrc)
            {
                foreach ($vtsrc as $field => $value)
                {
                    $eventData[$field] = $value;
                }
            }

            if (count($quote->getAllVisibleItems()) > 0)
            {
                $eventData['cart_json'] = $this->_helper->prepareCartJSON($quote, $id, $info);
                $eventData['cart_param'] = $this->_helper->prepareZaiusCart($quote, $id, $info);
                $eventData['cart_url'] = $this->_helper->prepareZaiusCartUrl($baseUrl) . $this->_helper->prepareZaiusCart($quote, $id, $info);
            }

            // call getsdkclient function
            $zaiusClient = $this->_sdk->getSdkClient();

            $zaiusstatus = $zaiusClient->postEvent(
                [
                    'type' => 'product',
                    'action' => 'add_to_cart',
                    'identifiers' => $identifiers,
                    'data' => $eventData
                ]
            );

            // check return values from the postevent function
            if ($zaiusstatus)
            {
                $this->_logger->info("The add_to_cart product Id " . $simpleProduct->getId() . " is passed successfully to zaius."); //saved in var/log/system.log
            } else
            {
                $this->_logger->info("The add_to_cart product id " . $simpleProduct->getId() . " is failed to zaius."); //saved in var/log/system.log
            }
        }
        catch (NoSuchEntityException $e)
        {
            $this->_logger->error($e->getMessage());
        }
        catch (ZaiusException $e)
        {
            $this->_logger->error($e->getMessage());
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e->getMessage());
        }

        // return
        return $this;
    }
}