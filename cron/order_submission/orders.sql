select distinct so.entity_id as 'Order Number',
	so.created_at as 'Date Placed',
    '' as 'SAP Delivery_Date',
    CONCAT(so.customer_firstname, ' ', so.customer_lastname) as 'Customer Name',
    soa.street as 'Customer Shipping Address Street',
    soa.city as 'Customer Shipping Address City',
    soa.region as 'Customer Shipping Address State',
    soa.postcode as 'Customer Shipping Address Zip',
    soi.sku as 'SMG SKU 1',
    soi.sku as 'Web SKU 1',
    soi.qty_ordered as 'Quantity 1',
    soi.product_id as 'Unit 1',
    soi.price as 'Unit Price 1',
    '' as 'SMG SKU 2',
    '' as 'Web SKU 2',
    '' as 'Quantity 2',
    '' as 'Unit 2',
    '' as 'Unit Price 2',
    so.grand_total as 'Gross Sales',
    so.shipping_amount as 'Shipping Amount',
    '0' as 'Exempt Amount',
    so.base_discount_amount as 'Discount Amount',
    so.subtotal as 'Subtotal',
    soi.tax_percent as 'Tax Rate',
    so.tax_amount as 'Sales Tax No Shipping',
    so.shipping_tax_amount as 'Sales Tax On Shipping',
    so.total_invoiced as 'Invoice Amount',
    so.shipping_description as 'Delivdery Location',
    so.customer_email as 'Customer Email',
    soa.telephone as 'Customer Phone',
    '' as 'Delivery Window'
from sales_order so
	inner join sales_order_item soi on soi.order_id = so.entity_id
    inner join sales_order_address soa on soa.parent_id = soi.order_id
where soa.address_type = 'shipping'
 and soi.product_type <> 'bundle'
 and so.created_at between @startdate and @enddate
order by so.entity_id;
