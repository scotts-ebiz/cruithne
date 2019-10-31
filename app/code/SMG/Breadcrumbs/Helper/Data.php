<?php
namespace SMG\Breadcrumbs\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
	/**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
	
		/**
	   * @var \Magento\Framework\App\Config\ScopeConfigInterface
	   */
	protected $_scopeConfig;
	
	protected $_cmspageManager;
	
	protected $_requestInterface;
   
   
	public function __construct(
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Cms\Model\Page $cmspageManager,
		\Magento\Framework\App\RequestInterface $requestInterface
		
	) {
		$this->_storeManager = $storeManager;
		$this->_scopeConfig = $scopeConfig;
		$this->_cmspageManager = $cmspageManager;
		$this->_requestInterface = $requestInterface;
		parent::__construct( $context );
	}
	
	public function isProductBreadcrumb()
	{
		return $this->_scopeConfig->getValue('web/default/show_product_breadcrumbs', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	
	public function isCmsBreadcrumb()
	{
		return $this->_scopeConfig->getValue('web/default/show_particularly_cms_breadcrumbs', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	
	public function getCmsPageManager()
	{
		return $this->_cmspageManager;
	}
	
	public function getCmsPageAction()
	{
		return $this->_requestInterface->getFullActionName();
	}
      
}