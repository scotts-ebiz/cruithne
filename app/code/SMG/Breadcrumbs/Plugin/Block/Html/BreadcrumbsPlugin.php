<?php
namespace SMG\Breadcrumbs\Plugin\Block\Html;

use Magento\Theme\Block\Html\Breadcrumbs;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class BreadcrumbsPlugin
{
	/**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
	
	/**
	* ScopeConfigInterface
    *
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
   */
   protected $_scopeConfig;
   
	/**
     * RequestInterface
     *
     * @var \Magento\Framework\App\RequestInterface
     */
   protected $_requestInterface;
   
   /**
     * Page
     *
     * @var \Magento\Cms\Model\Page
     */
   
   protected $_cmspageManager;

    
    public function __construct(
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\App\RequestInterface $requestInterface,
		\Magento\Cms\Model\Page $cmspageManager
    ) {
		$this->_storeManager = $storeManager;
		$this->_scopeConfig = $scopeConfig;
		$this->_requestInterface = $requestInterface;
		$this->_cmspageManager = $cmspageManager;
    }
	
    public function aroundAddCrumb(Breadcrumbs $breadcrumbs, callable $proceed, $crumbName, $crumbInfo)
    {
		$categoryBreadcrumbs = $this->_scopeConfig->getValue('web/default/show_category_breadcrumbs', ScopeInterface::SCOPE_STORE);
		$cmsBreadcrumbs = $this->_scopeConfig->getValue('web/default/show_particularly_cms_breadcrumbs', ScopeInterface::SCOPE_STORE);
		
		$actionName  = $this->_requestInterface->getFullActionName();
		$currentcmspage = $this->_cmspageManager->getIdentifier();
		
		$canshowBreadcrumbs = false;
		
		if($categoryBreadcrumbs == '1' && $actionName == 'catalog_category_view'){
			 $canshowBreadcrumbs = true;
		} 

		if($currentcmspage){
			$restrictedAction = explode(',', $cmsBreadcrumbs);
			if(in_array($currentcmspage, $restrictedAction)){
				$canshowBreadcrumbs = true;
			} 
		}
		
		if($canshowBreadcrumbs){

			$proceed($crumbName, $crumbInfo);
		}
    }
}