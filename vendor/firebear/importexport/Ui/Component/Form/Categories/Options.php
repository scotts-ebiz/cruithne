<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Firebear\ImportExport\Ui\Component\Form\Categories;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Category as CategoryModel;

/**
 * Options tree for "Categories" field
 */
class Options extends \Magento\Catalog\Ui\Component\Product\Form\Categories\Options
{
    /**
     * Retrieve categories tree
     *
     * @return array
     */
    protected function getCategoriesTree()
    {
        if ($this->categoriesTree === null) {
            $storeId = $this->request->getParam('store');
            $matchingCollection = $this->categoryCollectionFactory->create();

            $matchingCollection
                ->addAttributeToSelect('path')
                ->addAttributeToFilter(
                    'entity_id',
                    ['neq' => CategoryModel::TREE_ROOT_ID]
                )
                ->setStoreId($storeId);

            $shownCategoriesIds = [];

            /** @var \Magento\Catalog\Model\Category $category */
            foreach ($matchingCollection as $category) {
                foreach (explode('/', $category->getPath()) as $parentId) {
                    $shownCategoriesIds[$parentId] = 1;
                }
            }

            $collection = $this->categoryCollectionFactory->create();
            $collection
                ->addAttributeToFilter(
                    'entity_id',
                    ['in' => array_keys($shownCategoriesIds)]
                )
                ->addAttributeToSelect(['name', 'is_active', 'parent_id'])
                ->setOrder('entity_id', 'ASC')
                ->setStoreId($storeId);

            $categoryPaths = [];
            foreach ($collection as $category) {
                if ($category->hasChildren()) {
                    $this->recursiveCategory($category->getChildrenCategories(), $categoryPaths);
                }
            }
        }

        $this->categoriesTree = [];
        foreach ($categoryPaths as $path) {
            $this->categoriesTree[] = ['value' => $path, 'label' => $path];
        }

        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')
            ->debug(json_encode($this->categoriesTree));

        return $this->categoriesTree;
    }

    private function recursiveCategory($categoryChildren, &$categoryPaths = [])
    {
        foreach ($categoryChildren as $categoryChild) {
            $categoryPaths[] = $this->getCategoryPath($categoryChild);
        }
    }

    private function getCategoryPath($categoryChild)
    {
        $storeId = $this->request->getParam('store');

        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addAttributeToFilter('entity_id', $categoryChild->getId())
            ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
            ->setStoreId($storeId);
        $categoryFullPath = '';
        foreach ($collection as $category) {
            $categoryPath = $category->getPath();
            $explodeCategoryPath = explode('/', $categoryPath);
            foreach ($explodeCategoryPath as $categoryId) {
                if ($categoryId == 1) {
                    continue;
                }
                $collectionPathCategory = $this->categoryCollectionFactory->create();
                $collectionPathCategory->addAttributeToFilter('entity_id', $categoryId)
                    ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
                    ->setStoreId($storeId);
                foreach ($collectionPathCategory as $categoryPath) {
                    if (empty($categoryFullPath)) {
                        $categoryFullPath = $categoryPath->getName();
                    } else {
                        $categoryFullPath .= '/' . $categoryPath->getName();
                    }
                }
            }
        }

        return $categoryFullPath;
    }
}
