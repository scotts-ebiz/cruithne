<?php
namespace Freshrelevance\Digitaldatalayer\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    private $config;
    private $objectManager;
    private $pageTypeHelper;
    private $productHelper;
    private $mageMeta;
    private $customerGroup;
    private $locale;
    private $order;
    private $store;
    private $catModel;
    private $prodModel;
    private $configModel;
    private $urlIterface;
    private $requestInterface;

    public function __construct(
        \Freshrelevance\Digitaldatalayer\Helper\Config $config,
        \Freshrelevance\Digitaldatalayer\Helper\PageType $pageTypeHelper,
        \Freshrelevance\Digitaldatalayer\Helper\Product $productHelper,
        \Magento\Framework\App\ProductMetadataInterface $mageMeta,
        \Magento\Customer\Model\Group $customerGroup,
        \Magento\Framework\Locale\Resolver $locale,
        \Magento\Sales\Model\Order $order,
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Catalog\Model\Category $catModel,
        \Magento\Catalog\Model\ProductRepository $prodModel,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configModel,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->config = $config;
        $this->pageTypeHelper = $pageTypeHelper;
        $this->productHelper = $productHelper;
        $this->mageMeta = $mageMeta;
        $this->customerGroup = $customerGroup;
        $this->locale = $locale;
        $this->order = $order;
        $this->store = $store;
        $this->categoryModel = $catModel;
        $this->productModel = $prodModel;
        $this->configModel = $configModel;
        $this->urlInterface = $urlInterface;
        $this->requestInterface = $requestInterface;
        $this->customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
        $this->checkoutSession = $this->objectManager->get('Magento\Checkout\Model\Session');
        $this->_isScopePrivate = true;
    }
    public function getBaseData($pageCategoryTree = false)
    {
        $result=[
            'page'=>$this->getPageData($pageCategoryTree),
            'pluginversion'=>$this->getExtensionVersion(),
            'version'=>$this->getMagentoVersion(),
            'generatedDate'=>(time() * 1000)
        ];
        $isPageAvailable=$this->config->checkIsPageAvailableForDisposing($this->pageTypeHelper->getCurrentPage());
        if ($this->getCartData() && $isPageAvailable) {
            $result['cart']=$this->getCartData();
        }
        // Only show user data on uncacheable pages
        if (!$this->pageTypeHelper->isPageCacheable()) {
            $result['user'] = $this->getUserData();
        }
        return $result;
    }

    public function getExtensionVersion()
    {
        return '0.1.4';
    }

    public function getMagentoVersion()
    {
        $productMetadata = $this->mageMeta;
        return $productMetadata->getVersion();
    }

    public function getDdlCmsData()
    {
        $ddlData=$this->getBaseData();
        return json_encode($ddlData, JSON_UNESCAPED_SLASHES);
    }

    public function getDdlProductData($productId)
    {
        $ddlData = $this->getBaseData();
        $isPageAvailable=$this->config->checkIsPageAvailableForDisposing($this->pageTypeHelper->getCurrentPage());
        if ($productId && $isPageAvailable) {
            $product = $this->getProductData($productId);
            $ddlData['product'] = [$product];
        }

        return json_encode($ddlData, JSON_UNESCAPED_SLASHES);
    }

    public function getDdlProductCollectionData($block, $categoryTree, $compare = false)
    {
        $ddlData = $this->getBaseData($categoryTree);
        $isPageAvailable=$this->config->checkIsPageAvailableForDisposing($this->pageTypeHelper->getCurrentPage());
        if ($block && $isPageAvailable) {
            // Only load Product Collection if page is enabled to prevent loading issues
            if ($categoryTree) {
                $productCollection = $block->getProductsCollection();
            } else {
                $productCollection = $block->getItems();
            }
            $ddlData['product'] = [];
            foreach ($productCollection as $item) {
                $ddlData['product'][] = $this->getProductData($item->getId());
            }
        }

        return json_encode($ddlData, JSON_UNESCAPED_SLASHES);
    }

    public function getDdlCartData()
    {
        $ddlData = $this->getBaseData();
        $ddlData['cart'] = $this->getCartData();
        return json_encode($ddlData, JSON_UNESCAPED_SLASHES);
    }

    public function getDdlTransactionData()
    {
        $ddlData = $this->getBaseData();
        $ddlData['transaction'] = $this->getTransactionData();

        return json_encode($ddlData, JSON_UNESCAPED_SLASHES);
    }

    public function getUserData()
    {
        $userData = [];
        $userData['profile'] = [];
        $profile = [];
        $profile['profileInfo'] = [];
        $customerSession = $this->customerSession;
        $user = $customerSession->getCustomer();
        if($user){
            $user_id = $user->getEntityId();
            $firstName = $user->getFirstname();
            $lastName = $user->getLastname();
            $email = $user->getEmail();
            $groupId = $user->getGroupId();
            $groupCode = $this->customerGroup->load($groupId)->getCustomerGroupCode();

            if ($user_id) {
                $profile['profileInfo']['profileID'] = (string)$user_id;
                if ($this->config->getUserGroupExposure() == 1) {
                    $profile['profileInfo']['segment']['userGroupId'] = $groupId;
                    $profile['profileInfo']['segment']['userGroup'] = $groupCode;
                }
            }
            if ($firstName) {
                $profile['profileInfo']['userFirstName'] = $firstName;
            }
            if ($lastName) {
                $profile['profileInfo']['userLastName'] = $lastName;
            }
            if ($email) {
                $profile['profileInfo']['email'] = $email;
            }
        }
        $profile['profileInfo']['language'] = $resolver = $this->locale->getLocale();
        $profile['profileInfo']['returningStatus'] = $user ? 'true' : 'false';
        array_push($userData['profile'], $profile);

        return $userData;
    }

    public function getAddress($address)
    {
        $billing = [];
        if ($address) {
            $billing['line1'] = $address->getName();
            $billing['line2'] = $address->getStreetFull() ? $address->getStreetFull() : $address->getStreet()['0'];
            $billing['city'] = $address->getCity();
            $billing['postalCode'] = $address->getPostcode();
            $billing['country'] = $address->getCountryId();
            $state = $address->getRegion();
            $billing['stateProvince'] = $state ? $state : '';
        }
        return $billing;
    }

    public function getTransactionData()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $orderId = $order->getId();
        $transaction = [];
        if ($orderId) {
            $order = $this->order->load($orderId);
            // General details
            $transaction['transactionID'] = $order->getIncrementId();
            $transaction['total'] = [];
            $transaction['total']['currency'] = $order->getOrderCurrencyCode();
            $transaction['total']['basePrice'] = (float)$order->getSubtotal();
            $transaction['total']['transactionTotal'] = (float)$order->getGrandTotal();
            $transaction['total']['shipping'] = (float)$order->getShippingAmount();
            $transaction['total']['shippingMethod'] = $order->getShippingMethod() ? $order->getShippingMethod() : '';

            $voucher = $order->getCouponCode();
            $transaction['total']['voucherCode'] = $voucher ? $voucher : "";
            $voucher_discount = -1 * $order->getDiscountAmount();
            $transaction['total']['voucherDiscount'] = $voucher_discount ? $voucher_discount : 0;

            // Get addresses
            $transaction['profile'] = [];
            if ($order->getBillingAddress()) {
                $billingAddress = $order->getBillingAddress();
                $transaction['profile']['address'] = $this->getAddress($billingAddress);
            }
            if ($order->getShippingAddress()) {
                $shippingAddress = $order->getShippingAddress();
                $transaction['profile']['shippingAddress'] = $this->getAddress($shippingAddress);
            }
            // Add email
            if ($order->getCustomerEmail()) {
                $transaction['profile']['profileInfo'] = [];
                $transaction['profile']['profileInfo']['email'] = $order->getCustomerEmail();
            }
            // Get items
            $items = $order->getAllVisibleItems();
            $itemsData = $this->getItemsData($items);
            $transaction['items'] = $itemsData;
        }

        return $transaction;
    }

    public function getCartData()
    {
        $quote = $this->checkoutSession->getQuote();
        $cart=[];

        if ($quote) {
            $cart['cartID'] = $quote->getId();
            // Get Quote Details
            if ($quote->getSubtotal()) {
                $cart['price']['basePrice'] = (float)$quote->getSubtotal();
            } elseif ($quote->getBaseSubtotal()) {
                $cart['price']['basePrice'] = (float)$quote->getBaseSubtotal();
            } else {
                $cart['price']['basePrice'] = 0.0;
            }
            if ($quote->getShippingAddress()->getCouponCode()) {
                $cart['price']['voucherCode'] = $quote->getShippingAddress()->getCouponCode();
                $cart['price']['voucherDiscount'] = abs((float)$quote->getShippingAddress()->getDiscountAmount());
            }
            $cart['price']['currency'] = $quote->getQuoteCurrencyCode();
            if ($cart['price']['basePrice'] > 0.0) {
                $taxRate = (float)$quote->getShippingAddress()->getTaxAmount() / $quote->getSubtotal();
                $cart['price']['taxRate'] = round($taxRate, 3);
            }
            if ($quote->getShippingAmount()) {
                $cart['price']['shipping'] = (float)$quote->getShippingAmount();
                $cart['price']['shippingMethod'] = $quote->getShippingMethod();
                $cart['price']['priceWithTax'] = (float)$quote->getGrandTotal();
            } else {
                $cart['price']['priceWithTax'] = (float)$cart['price']['basePrice'];
            }
            if ($quote->getData()) {
                $getData = $quote->getData(); // To resolve a error on some versions of PHP.
                if (array_key_exists('grand_total', $getData)) {
                    $cart['price']['cartTotal'] = (float)$getData['grand_total'];
                } else {
                    $cart['price']['cartTotal'] = (float)$cart['price']['priceWithTax'];
                }
            } else {
                $cart['price']['cartTotal'] = (float)$cart['price']['priceWithTax'];
            }

            // Line items
            $items = $quote->getAllVisibleItems();
            if (!empty($items)) {
                $cart['items'] = $this->getItemsData($items);
            } else {
                return false;
            }
        }
        return $cart;
    }

    public function getCheckoutData()
    {
        return $this->getCartData();
    }

    public function getSelectedAttributeValues($values)
    {
        $returnDict = [];
        foreach ($values as $value) {
            $returnDict[$value['label']] = $value['value'];
        }
        return $returnDict;
    }

    public function getAttributeIdDict($attribute)
    {
        $attributeDict = [];
        foreach ($attribute['values'] as $opt) {
            $attributeDict[$opt['store_label']] = $opt['value_index'];
        }
        return $attributeDict;
    }

    public function getItemsData($items)
    {
        $store = $this->store->getStore();
        $categoryModel = $this->categoryModel;
        $productModel = $this->productModel;

        $lineItems = [];
        foreach ($items as $item) {
            $itemData = [];
            $itemPrice = [];
            $productId = $item->getProductId();
            $product = $productModel->getById($productId);
            $categories = [];
            foreach ($product->getCategoryIds() as $index => $cat) {
                if ($index == 0) {
                    $categories['primaryCategory'] = $categoryModel->load($cat)->getName();
                } else {
                    $categories['subCategory'.$index] = $categoryModel->load($cat)->getName();
                }
            }
            $categories['productType'] = $product->getTypeId();

            $itemPrice['basePrice'] = (float)$item->getPrice();
            $itemPrice['priceWithTax'] = (float)$item->getPriceInclTax();
            $itemPrice['currency'] = $store->getCurrentCurrency()->getCode();
            $productData = $this->getProductData($productId, $item);
            $itemData['productInfo'] = $productData['productInfo'];
            if (isset($productData['linkedProduct'])) {
                $itemData['linkedProduct'] = $productData['linkedProduct'];
            }
            $itemData['price'] = $itemPrice;
            $itemData['quantity'] = (float)($item->getQty());

            $itemData['category'] = $categories;
            $configCode = \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
            if ($item->getProduct()->getTypeId() == $configCode) {
                $itemData['attributes']['configurable_options'] = $this->getConfigOptions($item);
            }
            if ($item->getProduct()->getTypeId() == 'bundle') {
                $itemData['attributes']['bundle_options'] = $this->getBundleOptions($item);
            }
            $custom_options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
            if (isset($custom_options['options'])) {
                $itemData['attributes']['custom_options'] = $this->getCustomOptions($item);
            }
            array_push($lineItems, $itemData);
        }
        return $lineItems;
    }

    public function getProductData($productId, $item = null)
    {
        $productModel = $this->productModel;
        $product = $productModel->getById($productId);
        $productData=$this->productHelper->getProductData($product);
        $configCode = \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
        $groupedCode = \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE;
        // Check if Linked Product Exposure is enabled
        if ($this->config->linkedProductsAvailable() != 0) {
            if ($product->getTypeId() == $configCode) {
                if ($item) {
                    $productData['linkedProduct']=$this->productHelper->getConfigurableSelectedLinkedProducts($item);
                } else {
                    $productData['linkedProduct']=$this->productHelper->getConfigurableLinkedProducts($product);
                }
            } elseif ($product->getTypeId()=='bundle') {
                if ($item) {
                    $productData['linkedProduct']=$this->productHelper->getBundleSelectedLinkedProducts($item);
                } else {
                    $productData['linkedProduct']=$this->productHelper->getBundleLinkedProducts($product);
                }
            } elseif ($product->getTypeId()==$groupedCode) {
                // Only visible on Grouped Product Browse Pages
                $productData['linkedProduct']=$this->productHelper->getGroupedLinkedProducts($product);
            }
        }
        return $productData;
    }

    public function getPageData($categoryTree = [])
    {
        $urlInterface = $this->urlInterface;
        $localeResolver = $this->locale;
        $pageData = [];
        $pageData['pageInfo'] = [];
        $pageData['category'] = [];
        $referringURL = $this->requestInterface->getServer('HTTP_REFERER');

        $pageData['pageInfo']['pageName']=$this->pageTypeHelper->getPageTitle();
        $pageData['pageInfo']['destinationURL'] = $urlInterface->getCurrentUrl();
        if ($referringURL) {
            $pageData['pageInfo']['referringURL'] = $referringURL;
        }
        $pageData['pageInfo']['language'] = $localeResolver->getLocale();
        if ($categoryTree) {
            $pageData['category']['primaryCategory'] = $categoryTree['2'];
            if (count($categoryTree)>1) {
                $i=count($categoryTree);
                while ($i>1) {
                    $pageData['category']['subCategory'.($i-1)]=$categoryTree[$i+1];
                    $i--;
                }
            }
        }
        $pageData['category']['pageType'] = $this->pageTypeHelper->getPageType();
        return $pageData;
    }

    /**
     * Returns purchase complete query string
     * @return string
     */
    public function getPurchaseCompleteQs()
    {
        $customerSession = $this->customerSession;
        $order = $this->customerSession->getLastRealOrder();
        $orderId = false;
        if ($order->getId()) {
            $orderId = $order->getId();
            $email = $order->getCustomerEmail();
        } else {
            $email = $customerSession->getCustomer()->getEmail();
        }
        $qs = "e=" . urlencode($email);

        if ($orderId) {
            $qs = $qs . "&r=" . urlencode($orderId);
        }

        return $qs;
    }

    private function getConfigOptions($item)
    {
        $returnOptions = [];
        $selectedAttributes = $this->configModel->getSelectedAttributesInfo($item->getProduct());
        $configurableAttributes = $this->configModel->getConfigurableAttributesAsArray($item->getProduct());
        if ($selectedAttributes) {
            $i = 0;
            $valuesDict = $this->getSelectedAttributeValues($selectedAttributes);
            foreach ($configurableAttributes as $id => $attribute) {
                $attrIdDict = $this->getAttributeIdDict($attribute);
                $opt = [
                    'id' => $id,
                    'name' => $attribute['frontend_label']
                ];
                try {
                    if ($attribute['store_label']){
                        if(array_key_exists($attribute['store_label'], $valuesDict)) {
                            $opt['value'] = $valuesDict[$attribute['store_label']];
                        }
                        if(array_key_exists($opt['value'], $attrIdDict)) {
                            $opt['val_id'] = $attrIdDict[$opt['value']];
                        }
                    }
                } catch (\Exception $e) {}
                array_push($returnOptions, $opt);
                $i++;
            }
        }
        return $returnOptions;
    }

    private function getBundleOptions($item)
    {
        $returnOptions = [];
        $bundleOptions = $item->getProduct()->getTypeInstance(true)
            ->getOrderOptions($item->getProduct())['bundle_options'];
        foreach ($bundleOptions as $option) {
            array_push($itemData['attributes']['bundle_options'], [
                'id' => $option['option_id'],
                'name' => $option['label'],
                'val_id' => $option['value'],
                'value' => $option['value']
            ]);
        }
        return $returnOptions;
    }

    private function getCustomOptions($item)
    {
        $returnOptions = [];
        $customOptions = $item->getProduct()->getTypeInstance(true)
            ->getOrderOptions($item->getProduct())['options'];
        foreach ($customOptions as $option) {
            array_push($itemData['attributes']['custom_options'], [
                'id' => $option['option_id'],
                'name' => $option['label'],
                'val_id' => $option['option_value'],
                'value' => $option['value']
            ]);
        }
        return $returnOptions;
    }
}
