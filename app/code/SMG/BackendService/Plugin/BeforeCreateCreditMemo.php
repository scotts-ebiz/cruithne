<?php

namespace SMG\BackendService\Plugin;

use \Magento\Sales\Model\OrderFactory;
use Magento\Framework\Controller\ResultFactory;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\App\Response\RedirectInterface;
use \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Framework\App\Response\Http;
use Magento\Framework\UrlInterface;
use SMG\BackendService\Model\Client\Api;
use SMG\BackendService\Helper\Data as Config;
use \Magento\Framework\Webapi\Rest\Request;

class BeforeCreateCreditMemo
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Http
     */
    private $response;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Api
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * BeforeCreateCreditMemo constructor.
     * @param OrderFactory $orderFactory
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param RedirectInterface $reidrect
     */
    public function __construct(
        OrderFactory $orderFactory,
        ManagerInterface $messageManager,
        Http $response,
        UrlInterface $url,
        Api $client,
        Config $config
    ) {
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
        $this->response = $response;
        $this->url = $url;
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * @param CreditmemoLoader $order
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function beforeLoad(CreditmemoLoader $order)
    {
        $orderId = $order->getOrderId();

        if ($orderId) {
            $order = $this->orderFactory->create()->load($orderId);

            $canCreateCreditMemo = $this->client->execute(
                $this->config->getSapApiUrl(),
                $orderId,
                [],
                Request::HTTP_METHOD_GET
            );

            if ($order->getState() !== 'complete') {
                if ($canCreateCreditMemo == false) {
                    $url = $this->url->getUrl(
                        'sales/order/view',
                        [
                            'order_id' => $orderId,
                            'creditmemoerror' => 1
                        ]
                    );
                    $this->response->setRedirect($url);
                }
            }
        }
    }
}
