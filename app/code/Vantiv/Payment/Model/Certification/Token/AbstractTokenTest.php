<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Certification\Token;

use Magento\Framework\App\Config\ScopeConfigInterface as Config;
use Magento\Store\Model\StoreManagerInterface;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig as VantivConfig;
use Vantiv\Payment\Gateway\Certification\TestCommand as Command;
use Vantiv\Payment\Model\Certification\Test\ResultFactory;
use Vantiv\Payment\Gateway\Certification\Parser\TestResponseParserFactory;
use Vantiv\Payment\Gateway\Common\Renderer\RegisterTokenRenderer;
use Vantiv\Payment\Gateway\Cc\Parser\RegisterTokenResponseParserFactory;
use Vantiv\Payment\Model\Certification\AbstractTest;

/**
 * Certification test model
 */
abstract class AbstractTokenTest extends AbstractTest
{
    /**
     * Certification Test Command
     *
     * @var Command
     */
    private $command;

    /**
     * @var RegisterTokenRenderer
     */
    private $renderer = null;

    /**
     * @var RegisterTokenResponseParserFactory
     */
    private $tokenParserFactory = null;

    /**
     * Constructor
     *
     * @param Config $config
     * @param TestResponseParserFactory $parserFactory
     * @param Command $command
     * @param ResultFactory $resultFactory
     * @param StoreManagerInterface $storeManager
     * @param VantivConfig $vantivConfig
     * @param RegisterTokenRenderer $renderer
     * @param RegisterTokenResponseParserFactory $tokenParserFactory
     * @param array $data
     */
    public function __construct(
        Config $config,
        TestResponseParserFactory $parserFactory,
        Command $command,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager,
        VantivConfig $vantivConfig,
        RegisterTokenRenderer $renderer,
        RegisterTokenResponseParserFactory $tokenParserFactory,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $parserFactory,
            $storeManager,
            $vantivConfig,
            $resultFactory,
            $data
        );

        $this->command = $command;
        $this->renderer = $renderer;
        $this->tokenParserFactory = $tokenParserFactory;
    }

    /**
     * Run token test.
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject = [])
    {
        $renderer = $this->getTokenRenderer();

        foreach ($this->getRequestData() as $id => $data) {
            $data = array_replace_recursive($this->getDefaultRequestData(), $data);
            $request = $renderer->render($data);
            $response = $this->command->call($request);
            $parser = $this->createTokenParser($response);

            $responseData = $parser->toTransactionRawDetails();
            $success = $this->validate($responseData, $this->getResponseDataById($id));

            $this->persistResult([
                'test_id'      => $id,
                'name'         => $this->getName() . ', Register Token, Dataset "' . $id . '"',
                'store_id'     => $this->storeManager->getStore()->getId(),
                'merchant_id'  => $this->getVantivConfig()->getValue('merchant_id'),
                'order_id'     => $id,
                'litle_txn_id' => $parser->getLitleTxnId(),
                'success_flag' => $success,
                'request'      => $request,
                'response'     => $response,
            ]);
        }
    }

    /**
     * Get token renderer.
     *
     * @return \Vantiv\Payment\Gateway\Common\Renderer\RegisterTokenRenderer
     */
    private function getTokenRenderer()
    {
        return $this->renderer;
    }

    /**
     * Get token response parser.
     *
     * @param string $xml
     * @return \Vantiv\Payment\Gateway\Cc\Parser\RegisterTokenResponseParser
     */
    private function createTokenParser($xml)
    {
        return $this->tokenParserFactory->create(['xml' => $xml]);
    }

    /**
     * Get registration request test data.
     *
     * @return array
     */
    abstract protected function getRequestData();

    /**
     * Get registration response data.
     *
     * @return array
     */
    abstract protected function getResponseData();

    /**
     * Get specific test response data.
     *
     * @param string $id
     * @return array
     */
    private function getResponseDataById($id)
    {
        $data = $this->getResponseData();

        return $data[$id];
    }
}
