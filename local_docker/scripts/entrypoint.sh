#!/usr/bin/env bash

# get the filename of the current staging MySql backup
FILENAME='staging_'$(date +%Y%m%d)'0500.sql'

# Change the file name if there is a argument passed in
if [[ "$1" -eq "rc" ]]
   then
    FILENAME='rc_'$(date +%Y%m%d)'0031.sql'
fi
# copy the desired sql file to the temp directory for later use
/root/google-cloud-sdk/bin/gsutil cp gs://magento-data-export/$FILENAME /tmp/database.sql

sed -i "s/is_tracking_orders_on_frontend'/is_tracking_orders_on_frontend1'/" /tmp/database.sql
sed -i "s/is_tracking_orders_on_frontend'/is_tracking_orders_on_frontend2'/" /tmp/database.sql

# create the magento user
echo "CREATE USER 'magento'@'%' IDENTIFIED BY 'j7K9u3Lm2wA6';" | mysql -u root -pdfDF34#$

# grant permissions to the new magento user
echo "GRANT ALL PRIVILEGES ON *.* TO 'magento'@'%' IDENTIFIED BY 'j7K9u3Lm2wA6';" | mysql -u root -pdfDF34#$

# create the database
mysqladmin create magento -u magento -pj7K9u3Lm2wA6
echo "GRANT ALL PRIVILEGES ON magento.* TO 'magento'@'%' IDENTIFIED BY 'j7K9u3Lm2wA6';" | mysql -u root -pdfDF34#$ magento

# import the data into the magento database
mysql -u magento -pj7K9u3Lm2wA6 magento < /tmp/database.sql

# clean the database for use on the local server
if [[ "$1" -eq "rc" ]]
  then
    echo "update core_config_data set value = replace(value, 'rc', 'local') where value like '%rc%' and path = 'web/unsecure/base_url';" | mysql -u root -pdfDF34#$ magento
    echo "update core_config_data set value = replace(value, 'rc', 'local') where value like '%rc%' and path = 'web/secure/base_url';" | mysql -u root -pdfDF34#$ magento
    echo "update core_config_data set value = replace(value, 'rc', 'local') where value like '%rc%' and path = 'web/secure/base_link_url';" | mysql -u root -pdfDF34#$ magento
    echo "update core_config_data set value = replace(value, 'rc', 'local') where value like '%rc%' and path = 'web/unsecure/base_link_url';" | mysql -u root -pdfDF34#$ magento
    echo "update core_config_data set value = replace(value, 'rc', 'local') where value like '%rc%' and path = 'web/cookie/cookie_domain';" | mysql -u root -pdfDF34#$ magento
  else
    echo "update core_config_data set value = replace(value, 'staging', 'local') where value like '%staging%' and path = 'web/unsecure/base_url';" | mysql -u root -pdfDF34#$ magento
    echo "update core_config_data set value = replace(value, 'staging', 'local') where value like '%staging%' and path = 'web/secure/base_url';" | mysql -u root -pdfDF34#$ magento
    echo "update core_config_data set value = replace(value, 'staging', 'local') where value like '%staging%' and path = 'web/secure/base_link_url';" | mysql -u root -pdfDF34#$ magento
    echo "update core_config_data set value = replace(value, 'staging', 'local') where value like '%staging%' and path = 'web/unsecure/base_link_url';" | mysql -u root -pdfDF34#$ magento
    echo "update core_config_data set value = replace(value, 'staging', 'local') where value like '%staging%' and path = 'web/cookie/cookie_domain';" | mysql -u root -pdfDF34#$ magento
fi

# Not sure why this keeps getting added but removes extra stock inventroy view
echo "drop view inventory_stock_1;" | mysql -u root -pdfDF34#$ magento
