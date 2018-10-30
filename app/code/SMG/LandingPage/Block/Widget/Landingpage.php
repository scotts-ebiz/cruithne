<?php

namespace SMG\LandingPage\Block\Widget;

use Magento\Catalog\Api\ProductRepositoryInterface;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

use Magento\Store\Model\StoreManagerInterface;

use Magento\Widget\Block\BlockInterface;




class Landingpage extends Template implements BlockInterface
{
    protected $_template = "widget/landingpage.phtml";
    protected $_productRepository;
    protected $_storeManager;
    protected $_formKey;

    public function __construct(Context $context, ProductRepositoryInterface $productRepository, StoreManagerInterface $storeManager, FormKey $formKey, array $data = [])
    {
        $this->_productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->_formKey = $formKey;

        parent::__construct($context, $data);
    }

    /**
     * Get the Product object from the id
     *
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLoadProduct($id)
    {
        return $this->_productRepository->getById($id);
    }

    /**
     * Get the Product Image Url
     *
     * @param $image
     * @return string
     */
    public function getMediaBaseUrl($image)
    {
        return parse_url($this->getUrl(), PHP_URL_SCHEME) . '://' . parse_url($this->getUrl(), PHP_URL_HOST) . '/pub/media/catalog/product' . $image;
    }

    /**
     * Get the auto generated form key
     *
     * @return mixed
     */
    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
    }
}
