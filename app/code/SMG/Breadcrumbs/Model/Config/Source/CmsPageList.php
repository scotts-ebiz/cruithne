<?php

namespace SMG\Breadcrumbs\Model\Config\Source;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;


class CmsPageList implements \Magento\Framework\Option\ArrayInterface
{
	/**
     * @var array
     */
    protected $_options;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /** 
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->_collectionFactory = $collectionFactory;
    }
	
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = $this->_collectionFactory->create()->toOptionIdArray();
        }
        return $this->_options;
    }
}
?>