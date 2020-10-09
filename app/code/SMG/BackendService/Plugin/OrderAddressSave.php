<?php

namespace SMG\BackendService\Plugin;

use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Sales\Model\Order\AddressRepository;
use \Magento\Framework\Controller\ResultFactory;
use SMG\BackendService\Model\Client\Api;
use SMG\BackendService\Helper\Data as Config;
use \Magento\Sales\Api\Data\OrderAddressInterface;

class OrderAddressSave
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var AddressRepository
     */
    private $repositoryAddress;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Api
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * OrderAddressSave constructor.
     * @param Api $client
     * @param Config $config
     * @param ResultFactory $resultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressRepository $repositoryAddress
     */
    public function __construct(
        Api $client,
        Config $config,
        ResultFactory $resultFactory,
        OrderRepositoryInterface $orderRepository,
        AddressRepository $repositoryAddress
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->resultFactory = $resultFactory;
        $this->orderRepository = $orderRepository;
        $this->repositoryAddress = $repositoryAddress;
    }

    /**
     * @param \Magento\Sales\Controller\Adminhtml\Order\AddressSave $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(
        \Magento\Sales\Controller\Adminhtml\Order\AddressSave $subject,
        $result
    ) {
        $addressId = $subject->getRequest()->getParam('address_id');
        $address = $this->repositoryAddress->get($addressId);
        $orderId = $address->getParentId();
        $order = $this->orderRepository->get($orderId);

        $response = $this->client->execute(
            $this->config->getCustomerApiUrl(),
            "customer/updateAddresses",
            $this->buildOrderObject($order),
            Request::HTTP_METHOD_POST
        );

        return $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
        )->setPath('sales/*/view', ['order_id' => $orderId]);
    }

    public function buildOrderObject($order) {

        $shippingData = $order->getShippingAddress()->getData();
        $billingData = $order->getBillingAddress()->getData();

        $params = [];
        $params['source'] = $this->config->getWebSource();
        $params['transId'] = $this->config->generateUuid();
        $params['sourceService'] = $this->config->getWebSource();
        $params['customerId'] = $order->getCustomerId();
        $params['recurlyId'] = '';
        $params['billingFirstName'] = $billingData['firstname'];
        $params['billingLastName'] = $billingData['lastname'];
        $params['billingAddress'] = $billingData['street'];
        $params['billingCity'] = $billingData['city'];
        $params['billingState'] = $billingData['region'];
        $params['billingPostcode'] = $billingData['postcode'];
        $params['billingCountry'] = $billingData['country_id'];
        $params['shippingFirstName'] = $shippingData['firstname'];
        $params['shippingLastName'] = $shippingData['lastname'];
        $params['shippingAddress'] = $shippingData['street'];
        $params['shippingCity'] = $shippingData['city'];
        $params['shippingState'] = $shippingData['region'];
        $params['shippingPostcode'] = $shippingData['postcode'];
        $params['shippingCountry'] = $shippingData['country_id'];

        return $params;
    }

}
