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
use Magento\Backend\Model\Auth\Session;

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
     * @var Session
     */
    private $session;

    /**
     * BeforeCreateCreditMemo constructor.
     * @param OrderFactory $orderFactory
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param RedirectInterface $reidrect
     * @param Session $session
     */
    public function __construct(
        OrderFactory $orderFactory,
        ManagerInterface $messageManager,
        Http $response,
        UrlInterface $url,
        Api $client,
        Config $config,
        Session $session
    ) {
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
        $this->response = $response;
        $this->url = $url;
        $this->client = $client;
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * @param CreditmemoLoader $order
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function beforeLoad(CreditmemoLoader $order)
    {
        if ($this->config->getStatus()) {
            $orderId = $order->getOrderId();

            if ($orderId) {
                $order = $this->orderFactory->create()->load($orderId);

                $canCreateCreditMemo = $this->client->execute(
                    $this->config->getSapApiUrl(),
                    $orderId,
                    [],
                    Request::HTTP_METHOD_GET,
                    $this->session->isLoggedIn()
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
}
