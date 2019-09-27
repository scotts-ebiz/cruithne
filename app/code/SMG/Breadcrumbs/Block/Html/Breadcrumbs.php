<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\Breadcrumbs\Block\Html;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

/**
 * Html page breadcrumbs block
 *
 * @api
 * @since 100.0.2
 */
class Breadcrumbs extends \Magento\Framework\View\Element\Template
{
    /**
     * Current template name
     *
     * @var string
     */
    protected $_template = 'Magento_Theme::html/breadcrumbs.phtml';

    /**
     * List of available breadcrumb properties
     *
     * @var string[]
     */
    protected $_properties = ['label', 'title', 'link', 'first', 'last', 'readonly'];

    /**
     * List of breadcrumbs
     *
     * @var array
     */
    protected $_crumbs;

    /**
     * Cache key info
     *
     * @var null|array
     */
    protected $_cacheKeyInfo;

    /**
     * @var Json
     */
    private $serializer;
	
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
   
   protected $_requestInterface;
   
   protected $_cmspageManager;

    /**
     * @param Template\Context $context
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        Template\Context $context,
        array $data = [],
        Json $serializer = null,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\App\RequestInterface $requestInterface,
		\Magento\Cms\Model\Page $cmspageManager
    ) {
        parent::__construct($context, $data);
        $this->serializer =
            $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Json::class);
		$this->_storeManager = $storeManager;
		$this->_scopeConfig = $scopeConfig;
		$this->_requestInterface = $requestInterface;
		$this->_cmspageManager = $cmspageManager;
    }

    /**
     * Add crumb
     *
     * @param string $crumbName
     * @param array $crumbInfo
     * @return $this
     */
    public function addCrumb($crumbName, $crumbInfo)
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
			foreach ($this->_properties as $key) {
				if (!isset($crumbInfo[$key])) {
					$crumbInfo[$key] = null;
				}
			}

			if (!isset($this->_crumbs[$crumbName]) || !$this->_crumbs[$crumbName]['readonly']) {
				$this->_crumbs[$crumbName] = $crumbInfo;
			}
		}

        return $this;
    }

    /**
     * Get cache key informative items
     *
     * Provide string array key to share specific info item with FPC placeholder
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
			if ($this->_cacheKeyInfo === null) {
				$this->_cacheKeyInfo = parent::getCacheKeyInfo() + [
					'crumbs' => base64_encode($this->serializer->serialize($this->_crumbs)),
					'name' => $this->getNameInLayout()
				];
			}
        return $this->_cacheKeyInfo;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (is_array($this->_crumbs)) {
            reset($this->_crumbs);
            $this->_crumbs[key($this->_crumbs)]['first'] = true;
            end($this->_crumbs);
            $this->_crumbs[key($this->_crumbs)]['last'] = true;
        }
        $this->assign('crumbs', $this->_crumbs);

        return parent::_toHtml();
    }
}
