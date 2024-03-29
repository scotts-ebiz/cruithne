<?php

namespace SMG\BackendService\Plugin;

use SMG\BackendService\Model\Client\Api;
use SMG\BackendService\Helper\Data as Config;
use \Magento\Framework\Registry;
use \Magento\Framework\Webapi\Rest\Request;
use Magento\Backend\Model\Auth\Session;

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
     * @var Session
     */
    private $session;

    /**
     * AfterCreateCreditMemo constructor.
     * @param Api $client
     * @param Config $config
     * @param Registry $registry
     * @param Session $session
     */
    public function __construct(
        Api $client,
        Config $config,
        Registry $registry,
        Session $session
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->registry = $registry;
        $this->session = $session;
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
        if ($this->config->getStatus()) {
            $order = $this->registry->registry('current_creditmemo');
            if ($order) {

                $response = $this->client->execute(
                    $this->config->getOrderApiUrl(),
                    "orders/createOrderNote",
                    $this->buildOrderObject($order),
                    Request::HTTP_METHOD_POST,
                    $this->session->isLoggedIn()
                );

            }
        }
        return $result;
    }

    /**
     * @param $order
     * @return array
     */
    public function buildOrderObject($order)
    {
        $params = [];
        $params['transId'] = $this->config->generateUuid();
        $params['sourceService'] = $this->config->getWebSource();
        $params['orderId'] = $order->getOrderId();
        $params['noteType'] = 'email';
        $params['noteMessage'] = $order->getCustomerNote();
        $params['condition'] = 'success';

        return $params;
    }
}
