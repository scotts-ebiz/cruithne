#!/bin/bash

echo -e "\nStarting query to create and preload sales_order_delayed_notification table...\n";
if mysql -u magento -p$(grep -m1 password app/etc/env.php | cut -d "'" -f4) -h $(grep -m1 host app/etc/env.php | cut -d "'" -f4) magento -e < delayed_order_notification.sql 2>&1; then
    echo "\nSuccess - Table sales_order_delayed_notification created and pre-loaded.\n";
else
    echo "Failed to run successfully";
fi
