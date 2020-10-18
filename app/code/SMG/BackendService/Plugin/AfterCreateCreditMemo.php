<?php
namespace SMG\BackendService\Plugin;

use SMG\BackendService\Model\Client\Api;
use SMG\BackendService\Helper\Data as Config;
use \Magento\Framework\Registry;

class AfterCreateCreditMemo
{
    /**
     * @var Api
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * AfterCreateCreditMemo constructor.
     * @param Api $client
     * @param Config $config
     * @param Registry $registry
     */
    public function __construct(
        Api $client,
        Config $config,
        Registry $registry
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $subject
     * @param $result
     * @return mixed
     */
    public function afterSave(
        \Magento\Sales\Api\CreditmemoRepositoryInterface $subject,
        $result
    ) {
        $order = $this->registry->registry('current_creditmemo');

        $response = $this->client->execute(
            $this->config->getOrderApiUrl(),
            "orders/createOrderNote",
            $this->buildOrderObject($order),
            Request::HTTP_METHOD_POST
        );

        return $result;
    }

    /**
     * @param $order
     * @return array
     */
    public function buildOrderObject($order) {

        $params = [];
        $params['transId'] = $this->config->generateUuid();
        $params['sourceService'] = $this->config->getWebSource();
        $params['orderId'] = $order->getOrderId();
        $params['noteType'] = 'email';
        $params['noteMessage'] = 'Credit memo successfully created';
        $params['condition'] = 'success';

        return $params;
    }
}
