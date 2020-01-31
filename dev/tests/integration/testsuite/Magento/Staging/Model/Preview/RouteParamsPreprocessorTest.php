<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Staging\Model\Preview;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\UrlInterface;

class RouteParamsPreprocessorTest extends TestCase
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    protected function setUp()
    {
        $this->urlBuilder = ObjectManager::getInstance()->get(UrlInterface::class);
        $this->request = ObjectManager::getInstance()->get(RequestInterface::class);
    }

    public function testParamsAreNotAddedWhenNotInPreviewMode()
    {
        $this->request->setParams(
            [
                '_query' => [
                    StoreManagerInterface::PARAM_NAME => null,
                    VersionManager::PARAM_NAME => null,
                ],
                StoreManagerInterface::PARAM_NAME => null,
                VersionManager::PARAM_NAME => null,
            ]
        );
        $url = $this->urlBuilder->getUrl('customer/account/');
        self::assertNotContains(VersionManager::PARAM_NAME . '=', $url);
        self::assertNotContains('__signature=', $url);
        self::assertNotContains('__timestamp=', $url);
    }

    public function testParamsAreAddedWhenInPreviewMode()
    {
        $this->request->setParams(
            [
                '_query' => [
                    StoreManagerInterface::PARAM_NAME => '1',
                    VersionManager::PARAM_NAME => '123456789',
                ],
                StoreManagerInterface::PARAM_NAME => '1',
                VersionManager::PARAM_NAME => '123456789',
            ]
        );
        $url = $this->urlBuilder->getUrl('customer/account/');
        self::assertContains(VersionManager::PARAM_NAME . '=', $url);
        self::assertContains('__signature=', $url);
        self::assertContains('__timestamp=', $url);
    }

    public function testVersionParamIsNotAddedWhenStoreParamIsNotSet()
    {
        $this->request->setParams(
            [
                '_query' => [
                    StoreManagerInterface::PARAM_NAME => null,
                    VersionManager::PARAM_NAME => '123456789',
                ],
                StoreManagerInterface::PARAM_NAME => null,
                VersionManager::PARAM_NAME => '123456789',
            ]
        );
        $url = $this->urlBuilder->getUrl('customer/account/');
        self::assertNotContains(VersionManager::PARAM_NAME . '=', $url);
        self::assertContains('__signature=', $url);
        self::assertContains('__timestamp=', $url);
    }
}
