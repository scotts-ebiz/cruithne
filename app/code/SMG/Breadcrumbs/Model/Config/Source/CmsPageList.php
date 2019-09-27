<?php

namespace SMG\Breadcrumbs\Model\Config\Source;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;


class CmsPageList implements \Magento\Framework\Option\ArrayInterface
{
	/**
     * @var array
     */
    protected $options;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }
	
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->collectionFactory->create()->toOptionIdArray();
        }
        return $this->options;
    }
}
?>