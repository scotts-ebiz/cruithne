<?php
namespace Freshrelevance\Digitaldatalayer\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class QueryString extends AbstractHelper
{
    private $objectManager;
    private $request;

    public function __construct()
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->request = $this->objectManager->get('\Magento\Framework\App\Request\Http');
    }
    
    public function getCartRebuildQString()
    {
        return $this->request->getParam('cart_rebuild', false);
    }
    
    public function getRedirectedQString()
    {
        return $this->request->getParam('redirected', false);
    }
}
