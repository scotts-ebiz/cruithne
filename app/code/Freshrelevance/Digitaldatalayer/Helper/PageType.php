<?php
namespace Freshrelevance\Digitaldatalayer\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class PageType extends AbstractHelper
{
    const CATALOG_PRODUCT_VIEW='catalog_product_view';
    const CATALOG_CATEGORY_VIEW='catalog_category_view';
    const CATALOG_SEARCH='catalogsearch_result_index';
    const CMS='cms_page';
    const CART='checkout_cart_index';
    const CONFIRMATION='checkout_onepage_success';
    const CHECKOUT='checkout_index_index';
    const HOME='cms_index_index';
    const ACCOUNT='customer_account_index';
    const LOGIN='customer_account_login';
    const REGISTER='customer_account_create';
    //const

    private $pages = [];
    private $request;
    private $title;
    private $config;
    public function __construct(
        \Magento\Framework\View\Page\Title $title,
        \Magento\Framework\View\Layout $subject,
        \Freshrelevance\Digitaldatalayer\Helper\Config $config
    ) {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->request = $this->objectManager->get('\Magento\Framework\App\Request\Http');
        $this->title = $title;
        $this->subject = $subject;
        $this->config = $config;
        $this->pages=[
            $this::CATALOG_PRODUCT_VIEW=>'product',
            $this::CATALOG_CATEGORY_VIEW=>'category',
            $this::CATALOG_SEARCH=>'search',
            $this::CMS=>'cms',
            $this::CART=>'cart',
            $this::CONFIRMATION=>'confirmation',
            $this::CHECKOUT=>'checkout',
            $this::HOME=>'home',
            $this::ACCOUNT=>'account',
            $this::LOGIN=>'login',
            $this::REGISTER=>'register'
        ];
    }

    public function getPageType()
    {
        if (isset($this->pages[$this->request->getFullActionName()])) {
            return $this->pages[$this->request->getFullActionName()];
        } else {
            return $this->request->getFullActionName();
        }
    }
    public function getCurrentPage()
    {
        return $this->request->getFullActionName();
    }
    public function getPageTitle()
    {
        return $this->title->getShort();
    }
    public function isPageCacheable()
    {
        return $this->subject->isCacheable();
    }
    public function isTransactionDataAvailable()
    {
        $current = $this->getCurrentPage();
        $custom_expose = false;
        $custom = $this->config->getExposedTransactionPages();
        if ($custom) {
            $custom_expose = in_array($current, explode(',', $custom));
        }
        return ($custom_expose || ($current == 'checkout_onepage_success'));
    }
}
