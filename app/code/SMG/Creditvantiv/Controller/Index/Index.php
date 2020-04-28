<?php
namespace SMG\Creditvantiv\Controller\Index;

use SMG\Creditvantiv\Model\Payment;
use Vantiv\Payment\Gateway\Common\Client\HttpClient as Client;

class Index extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
    protected $_modelPayment;
    private $order;
    protected $client;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Magento\Sales\Model\Order $order,
         Payment $modelPayment,
         Client $client)
	{
		$this->_pageFactory = $pageFactory;
		$this->_modelPayment = $modelPayment;
		$this->order = $order;
		$this->client = $client;
		return parent::__construct($context);
	}

	public function execute()
	{
        
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $creditpayment = $objectManager->create('SMG\Creditvantiv\Model\Payment');
        $creditpaymentadd = $objectManager->create('SMG\Creditvantiv\Model\Payment');
        $collection = $creditpayment->getCollection()->addFieldToFilter(['status'],
    [
        ['eq' => 'pending']
    ]); //Get Collection of module data
         $creditxml='';
        foreach ($collection as $value) {
        	$entity_id = $value->getEntityId();
        	$order_id = $value->getOrderId();
        	$credit_id = $value->getCreditId();
            $credit_amount = $value->getCreditAmount();
            $credit_note = $value->getCreditNote();
            
            $order = $this->order->load($order_id);
            $order_date = $order->getCreatedAt();
            $date = date_create($order_date);
            $createddate=date_format($date, 'Y-m-d');
            $customer_id = $order->getCustomerId();
            $tax_amount = $order->getTaxAmount();
            $discount_amount = $order->getBaseDiscountAmount(); 
            $shipping_amount = $order->getShippingAmount();      
            $postcode = $order->getShippingAddress()->getData("postcode");
            $postcode = substr($postcode, 0, 5);
            $coundtry_id = $order->getShippingAddress()->getData("country_id");
            $orderItems = $order->getAllItems();
            $i=1;
            $tax_percent = 0;
            $itemxml='';
            foreach($orderItems as $items)
            {
               $description = $items->getDescription(); 
               $item_id = $items->getItemId();
               $qty_ordered = $items->getQtyOrdered();
               $tax_amount_item = $items->getTaxAmount();
               $price = $items->getPrice();
               $row_total_incl_tax = $items->getRowTotalInclTax();
               $discount_amount_item = $items->getDiscountAmount();
               $base_price = $items->getBasePrice();
               $tax_percent_item = $items->getTaxPercent();
               $tax_percent = $tax_percent + $tax_percent_item;
               $tax_amount_item = $items->getTaxAmount();

               $itemxml .= '<lineItemData>
				<itemSequenceNumber>'.$i.'</itemSequenceNumber>
				<itemDescription>'.$description.'</itemDescription>
				<productCode>'.$item_id.'</productCode>
				<quantity>'.$qty_ordered.'</quantity>
				<unitOfMeasure>EACH</unitOfMeasure>
				<taxAmount>'.$tax_amount_item.'</taxAmount>
				<lineItemTotal>'.$price.'</lineItemTotal>
				<lineItemTotalWithTax>'.$row_total_incl_tax.'</lineItemTotalWithTax>
				<itemDiscountAmount>'.$discount_amount_item.'</itemDiscountAmount>
				<commodityCode>300</commodityCode>
				<unitCost>'.$base_price.'</unitCost>

				<detailTax>
					<taxIncludedInTotal>true</taxIncludedInTotal>
					<taxAmount>'.$tax_amount_item.'</taxAmount>
					<taxRate>'.$tax_percent_item.'</taxRate>
					<taxTypeIdentifier>03</taxTypeIdentifier>
					<cardAcceptorTaxId>011234567</cardAcceptorTaxId>
				</detailTax>
			</lineItemData>'; 
              $i++;

            }


            $creditxml='<?xml version="1.0" encoding="UTF-8"?>
            <cnpRequest version="12.0" xmlns="http://www.vantivcnp.com/schema" id="'.$entity_id.'" numBatchRequests="1">
	<authentication>
		<user>AEROVAULT</user>
		<password>344e6%*3c%4be</password>
	</authentication>
<batchRequest id="'.$entity_id.'" numAuths="0" authAmount="0" numCaptures="0" captureAmount="0" numCredits="1" creditAmount="'.$credit_amount.'" 
numSales="0" saleAmount="0" merchantId="9300268">
	<credit id="SMG1" reportGroup="RG1">
			<cnpTxnId>84568456</cnpTxnId>
			<amount>'.$credit_amount.'</amount>
		<enhancedData>
			<customerReference>'.$customer_id.'</customerReference>
			<salesTax>'.$tax_amount.'</salesTax>
			<taxExempt>false</taxExempt>
			<discountAmount>'.$discount_amount.'</discountAmount>
			<shippingAmount>'.$shipping_amount.'</shippingAmount>
			<dutyAmount>0</dutyAmount>
			<shipFromPostalCode>01851</shipFromPostalCode>
			<destinationPostalCode>'.$postcode.'</destinationPostalCode>
			<destinationCountryCode>'.$coundtry_id.'</destinationCountryCode>
			<invoiceReferenceNumber>'.$order_id.'</invoiceReferenceNumber>
			<orderDate>'.$createddate.'</orderDate>
			
			<detailTax>
				<taxIncludedInTotal>true</taxIncludedInTotal>
				<taxAmount>'.$tax_amount.'</taxAmount>
				<taxRate>'.$tax_percent.'</taxRate>
				<taxTypeIdentifier>00</taxTypeIdentifier>
				<cardAcceptorTaxId>011234567</cardAcceptorTaxId>
			</detailTax>
			'.$itemxml.'
			</enhancedData>
	</credit>
</batchRequest></cnpRequest>'; 

    $addstatus = $creditpaymentadd->load( $entity_id,'entity_id');
      try {

			$url = 'https://www.testvantivcnp.com/sandbox/communicator/online';
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $creditxml);
			$result = curl_exec($ch);
			curl_close($ch);
             
             print_r($result);exit;
			  
			  $addstatus->setStatus('complete');
              $addstatus->save();
      	
      } catch (Exception $e) {
           
            $addstatus->setStatus('failed');
            $addstatus->save();

      	echo 'Message: ' .$e->getMessage();
      }
       

        	# code...
        }
        //print_r($collection->getData());
		//return $this->_pageFactory->create();
	}
}