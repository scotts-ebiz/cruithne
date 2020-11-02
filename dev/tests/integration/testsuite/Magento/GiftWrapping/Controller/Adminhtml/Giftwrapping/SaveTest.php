<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\GiftWrapping\Model\Wrapping;
use Magento\GiftWrapping\Model\WrappingRepository;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Test for @see \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping\Save.
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var WriteInterface
     */
    private $pubDirectory;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        /** @var Filesystem $filesystem */
        $this->filesystem = $this->_objectManager->get(Filesystem::class);
        $this->pubDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::PUB);
        $this->resource = 'Magento_GiftWrapping::magento_giftwrapping';
        $this->uri = 'backend/admin/giftwrapping/save';
    }

    /**
     * Test save controller.
     *
     * @dataProvider saveProvider
     * @param array $image
     * @param array $postData
     * @param array $expects
     * @return void
     */
    public function testSave(array $image, array $postData, array $expects): void
    {
        $fileName = 'magento_small_image.jpg';
        $fixtureDir = realpath(__DIR__ . '/../../../_files');
        $filePath = $this->pubDirectory->getAbsolutePath($fileName);
        $this->copyFile($fixtureDir . '/' . $fileName, $filePath);

        $_FILES['image_name'] = $image;
        $_FILES['image_name']['tmp_name'] = $filePath;

        $this->getRequest()
            ->setPostValue('wrapping', $postData)
            ->setMethod(Http::METHOD_POST);
        $dispatchUrl = $this->uri . '/store/' . Store::DEFAULT_STORE_ID . '/';
        $this->dispatch($dispatchUrl);

        $this->assertSessionMessages(
            $this->contains("You saved the gift wrapping."),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('backend/admin/giftwrapping'));

        $model = $this->getLastWrappingModel();
        $this->assertEquals($expects['website_ids'], $model->getWebsiteIds());
        $this->assertEquals($expects['status'], $model->getStatus());
        $this->assertEquals($expects['base_price'], $model->getBasePrice());
        $imageNamePattern = '/fooImage[_0-9]*\./';
        $this->assertRegExp($imageNamePattern, $model->getImage());
        $this->assertNull($model->getTmpImage());
    }

    /**
     * Check controller when data is incorrect.
     *
     * @return void
     */
    public function testSaveFail(): void
    {
        $postData = [
            'website_ids' => [
                Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getWebsite()->getId()
            ],
            'status' => 1,
            'base_price' => 15,
        ];
        $this->getRequest()
            ->setPostValue('wrapping', $postData)
            ->setMethod(Http::METHOD_POST);
        $dispatchUrl = $this->uri . '/store/' . Store::DEFAULT_STORE_ID . '/';
        $this->dispatch($dispatchUrl);

        $this->assertSessionMessages(
            $this->contains("Field is required: Gift Wrapping Design"),
            MessageInterface::TYPE_ERROR
        );
        $this->assertRedirect($this->stringContains('backend/admin/giftwrapping/edit'));
    }

    /**
     * Copy file from source path to destination path.
     *
     * @param $sourcePath
     * @param $destinationPath
     */
    private function copyFile($sourcePath, $destinationPath): void
    {
        /** @var WriteInterface $rootDirectory */
        $rootDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $rootDirectory->copyFile($sourcePath, $destinationPath);
    }

    /**
     * Get last wrapping model from repository.
     *
     * @return Wrapping
     */
    private function getLastWrappingModel(): Wrapping
    {
        /** @var WrappingRepository $wrappingRepository */
        $wrappingRepository = $this->_objectManager->get(WrappingRepository::class);
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('status', '1')->create();
        $items = $wrappingRepository->getList($searchCriteria)->getItems();

        return end($items);
    }

    /**
     * Save test data provider.
     *
     * @return array
     */
    public function saveProvider(): array
    {
        return [
            [
                [
                    'name' => 'fooImage.jpg',
                    'type' => 'image/jpeg',
                    'error' => 0,
                    'size' => 12500,
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [
                        Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getWebsite()->getId()
                    ],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => ['value' => 'fooImage.jpg'],
                ],
                [
                    'website_ids' => [
                        Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getWebsite()->getId()
                    ],
                    'status' => 1,
                    'base_price' => 15,
                ]
            ],
            [
                [
                    'name' => 'fooImage.jpg',
                    'type' => 'image/jpeg',
                    'error' => 0,
                    'size' => 12500,
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [
                        Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getWebsite()->getId()
                    ],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => ['value' => 'fooImage.jpg'],
                    'tmp_image' => 'barImage.jpg',
                ],
                [
                    'website_ids' => [
                        Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getWebsite()->getId()
                    ],
                    'status' => 1,
                    'base_price' => 15,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass()
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var WrappingRepository $wrappingRepository */
        $wrappingRepository = $objectManager->get(WrappingRepository::class);
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('status', '1')->create();
        $items = $wrappingRepository->getList($searchCriteria)->getItems();
        foreach ($items as $item) {
            $wrappingRepository->delete($item);
        }
        $filesystem = $objectManager->get(Filesystem::class);
        /** @var WriteInterface $directory */
        $directory = $filesystem->getDirectoryWrite(DirectoryList::PUB);
        if ($directory->isExist('media/wrapping')) {
            $directory->delete('media/wrapping');
        }
    }
}
