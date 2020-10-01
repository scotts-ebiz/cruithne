<?php

namespace SMG\OrderEdit\Plugin;

use \Magento\Sales\Api\Data\OrderAddressInterface;
use \GuzzleHttp\Client;
use \GuzzleHttp\ClientFactory;
use \GuzzleHttp\Exception\GuzzleException;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Psr7\ResponseFactory;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Sales\Model\Order\AddressRepository;
use \Magento\Framework\Controller\ResultFactory;
use \Psr\Log\LoggerInterface;

class AddressSave
{
    const API_REQUEST_URI = '//cts-customers-k6x4iqtnzq-uc.a.run.app/v1/customer/updateAddresses';

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        ResultFactory $resultFactory,
        OrderRepositoryInterface $orderRepository,
        AddressRepository $repositoryAddress,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->resultFactory = $resultFactory;
        $this->orderRepository = $orderRepository;
        $this->repositoryAddress = $repositoryAddress;
        $this->logger = $logger;
    }

    public function afterExecute(
        \Magento\Sales\Controller\Adminhtml\Order\AddressSave $subject,
        $result
    ) {
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => self::API_REQUEST_URI
        ]]);

        $addressId = $subject->getRequest()->getParam('address_id');
        $address = $this->repositoryAddress->get($addressId);
        $orderId = $address->getParentId();
        $order = $this->orderRepository->get($orderId);

        $shippingData = $order->getShippingAddress()->getData();
        $billingData = $order->getBillingAddress()->getData();

        $params = "
				'source' => 'Web',
				'transId' => '',
				'sourceService' => '',
				'customerId' => " . $order->getCustomerId() . ",
				'recurlyId' => '',
				'billingFirstName' => " . $billingData['firstname'] . ",
				'billingLastName' => " . $billingData['lastname'] . ",
				'billingAddress' => " . $billingData['street'] . ",
				'billingCity' => " . $billingData['city'] . ",
				'billingState' => " . $billingData['region'] . ",
				'billingPostcode' => " . $billingData['postcode'] . ",
				'billingCountry' => " . $billingData['country_id'] . ",
				'shippingFirstName' => " . $shippingData['firstname'] . ",
				'shippingLastName' => " . $shippingData['lastname'] . ",
				'shippingAddress' => " . $shippingData['street'] . ",
				'shippingCity' => " . $shippingData['city'] . ",
				'shippingState' => " . $shippingData['region'] . ",
				'shippingPostcode' => " . $shippingData['postcode'] . ",
				'shippingCountry' => " . $shippingData['country_id'] . "";

        try {
            $response = $client->request(
                Request::HTTP_METHOD_POST,
                $params
            );
            $this->logger->info($response->getBody());
        } catch (\Exception $ex) {
            //TODO
        }

        //TODO - Logic for Response

        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        return $redirect->setPath('sales/*/view', ['order_id' => $orderId]);
    }

}
