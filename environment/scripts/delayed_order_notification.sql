-- ECOM-1760: Create New Table to hold delayed shipping status.
CREATE TABLE magento.sales_order_delayed_notification (
    order_id VARCHAR(32),
    email_sent_status BOOLEAN,
    PRIMARY KEY (order_id)
);

-- ECOM-1760: Pre-load table with orders older than 10 day.
-- There are orders with bad statuses and we don't want those customers notified.
INSERT INTO magento.sales_order_delayed_notification (order_id, email_sent_status)
SELECT increment_id, 0
FROM magento.sales_order
WHERE store_id !='1' AND created_at < DATE_SUB(NOW(),INTERVAL 10 DAY);
